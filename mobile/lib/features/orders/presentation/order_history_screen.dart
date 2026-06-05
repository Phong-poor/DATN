import 'package:flutter/material.dart';

import '../../../app/app_dependencies.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/order.dart';
import '../../../shared/widgets/commerce_widgets.dart';
import '../../../shared/widgets/network_image_box.dart';
import '../../../shared/widgets/state_content.dart';

class OrderHistoryScreen extends StatefulWidget {
  const OrderHistoryScreen({super.key});

  @override
  State<OrderHistoryScreen> createState() => _OrderHistoryScreenState();
}

class _OrderHistoryScreenState extends State<OrderHistoryScreen> {
  bool _loading = true;
  String? _error;
  List<Order> _orders = const [];

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_loading && _orders.isEmpty && _error == null) _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      _orders = await AppScope.of(context).orderService.getOrders();
    } catch (exception) {
      _error = '$exception';
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _cancel(Order order) async {
    try {
      await AppScope.of(context).orderService.cancel(order.id);
      await _load();
    } catch (exception) {
      _show(exception);
    }
  }

  Future<void> _reorder(Order order) async {
    try {
      final message = await AppScope.of(context).orderService.reorder(order.id);
      _show(message);
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
      appBar: AppBar(
        title: const Text('Đơn mua'),
        actions: [
          IconButton(
            tooltip: 'Tải lại',
            onPressed: _load,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: StateContent(
        loading: _loading,
        error: _error,
        empty: !_loading && _orders.isEmpty,
        emptyMessage: 'Bạn chưa có đơn hàng nào',
        onRetry: _load,
        child: RefreshIndicator(
          onRefresh: _load,
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(12, 8, 12, 24),
            itemCount: _orders.length,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (context, index) {
              final order = _orders[index];
              return _OrderCard(
                order: order,
                onCancel: () => _cancel(order),
                onReorder: () => _reorder(order),
              );
            },
          ),
        ),
      ),
    );
  }
}

class _OrderCard extends StatelessWidget {
  const _OrderCard({
    required this.order,
    required this.onCancel,
    required this.onReorder,
  });

  final Order order;
  final VoidCallback onCancel;
  final VoidCallback onReorder;

  @override
  Widget build(BuildContext context) {
    final status = _orderStatus(order.status);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Đơn hàng #${order.id}',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                ),
                StatusPill(label: status.label, color: status.color),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              order.createdAt,
              style: const TextStyle(color: AppColors.muted, fontSize: 11),
            ),
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 10),
              child: Divider(),
            ),
            for (final item in order.items.take(2)) ...[
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: NetworkImageBox(
                      url: item.imageUrl,
                      width: 58,
                      height: 58,
                      fit: BoxFit.contain,
                    ),
                  ),
                  const SizedBox(width: 9),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item.productName,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          '${item.variantName} • x${item.quantity}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: AppColors.muted,
                            fontSize: 10,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    formatMoney(item.price),
                    style: const TextStyle(
                      color: AppColors.danger,
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
            ],
            if (order.items.length > 2)
              Text(
                '+ ${order.items.length - 2} sản phẩm khác',
                style: const TextStyle(color: AppColors.muted, fontSize: 11),
              ),
            const Divider(),
            Row(
              children: [
                const Expanded(
                  child: Text(
                    'Thành tiền',
                    style: TextStyle(color: AppColors.muted, fontSize: 12),
                  ),
                ),
                Text(
                  formatMoney(order.total),
                  style: const TextStyle(
                    color: AppColors.danger,
                    fontSize: 16,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                if (order.status == 'pending' || order.status == 'confirmed')
                  OutlinedButton(
                    onPressed: onCancel,
                    child: const Text('Hủy đơn'),
                  ),
                if (order.status == 'pending' || order.status == 'confirmed')
                  const SizedBox(width: 8),
                FilledButton.icon(
                  onPressed: onReorder,
                  icon: const Icon(Icons.replay, size: 18),
                  label: const Text('Mua lại'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

({String label, Color color}) _orderStatus(String value) {
  switch (value.toLowerCase()) {
    case 'pending':
      return (label: 'CHỜ XÁC NHẬN', color: AppColors.warning);
    case 'confirmed':
      return (label: 'ĐÃ XÁC NHẬN', color: AppColors.primary);
    case 'shipping':
      return (label: 'ĐANG GIAO', color: Color(0xFF7C3AED));
    case 'completed':
    case 'delivered':
      return (label: 'HOÀN THÀNH', color: AppColors.success);
    case 'cancelled':
    case 'canceled':
      return (label: 'ĐÃ HỦY', color: AppColors.danger);
    default:
      return (label: value.toUpperCase(), color: AppColors.muted);
  }
}
