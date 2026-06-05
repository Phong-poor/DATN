import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../app/app_dependencies.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/content.dart';
import '../../../shared/widgets/state_content.dart';

class PromotionsScreen extends StatefulWidget {
  const PromotionsScreen({super.key});

  @override
  State<PromotionsScreen> createState() => _PromotionsScreenState();
}

class _PromotionsScreenState extends State<PromotionsScreen> {
  bool _loading = true;
  String? _error;
  List<Promotion> _items = const [];

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
      _items = await AppScope.of(context).contentService.getPromotions();
    } catch (exception) {
      _error = '$exception';
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _copy(Promotion item) async {
    await Clipboard.setData(ClipboardData(text: item.code));
    if (mounted) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Đã sao chép mã ${item.code}')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Kho ưu đãi')),
      body: StateContent(
        loading: _loading,
        error: _error,
        onRetry: _load,
        empty: !_loading && _items.isEmpty,
        emptyMessage: 'Hiện chưa có chương trình khuyến mãi',
        child: RefreshIndicator(
          onRefresh: _load,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(12, 8, 12, 24),
            children: [
              const _PromotionHeader(),
              const SizedBox(height: 12),
              for (final item in _items) ...[
                _PromotionCard(item: item, onCopy: () => _copy(item)),
                const SizedBox(height: 10),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _PromotionHeader extends StatelessWidget {
  const _PromotionHeader();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: AppColors.navy,
        borderRadius: BorderRadius.circular(8),
      ),
      child: const Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'ƯU ĐÃI ĐỘC QUYỀN',
                  style: TextStyle(
                    color: AppColors.warning,
                    fontSize: 10,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                SizedBox(height: 5),
                Text(
                  'Săn mã giảm giá\nmỗi ngày',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    height: 1.1,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ],
            ),
          ),
          Icon(Icons.local_offer_outlined, color: AppColors.warning, size: 62),
        ],
      ),
    );
  }
}

class _PromotionCard extends StatelessWidget {
  const _PromotionCard({required this.item, required this.onCopy});

  final Promotion item;
  final VoidCallback onCopy;

  @override
  Widget build(BuildContext context) {
    final color = item.category == 'freeship'
        ? AppColors.success
        : item.category == 'birthday'
        ? const Color(0xFF7C3AED)
        : AppColors.danger;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 82,
              height: 96,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(7),
              ),
              alignment: Alignment.center,
              child: Text(
                item.offerLabel,
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: color,
                  fontSize: 13,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.name,
                    style: Theme.of(context).textTheme.titleSmall,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    item.description.isEmpty
                        ? item.condition > 0
                              ? 'Áp dụng cho đơn từ ${formatMoney(item.condition)}'
                              : 'Áp dụng theo điều kiện chương trình'
                        : item.description,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: AppColors.muted,
                      fontSize: 11,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 7,
                          ),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF8FAFC),
                            border: Border.all(color: AppColors.border),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            item.code,
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      SizedBox(
                        height: 34,
                        child: FilledButton(
                          onPressed: onCopy,
                          child: const Text('Sao chép'),
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
    );
  }
}
