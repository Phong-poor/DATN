import 'package:flutter/material.dart';

import '../../core/theme/app_theme.dart';
import '../../core/utils/parsers.dart';
import '../models/category.dart';
import '../models/product.dart';
import 'network_image_box.dart';

class BrandMark extends StatelessWidget {
  const BrandMark({this.compact = false, super.key});

  final bool compact;

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      'assets/images/predator_group.png',
      height: compact ? 32 : 46,
      cacheWidth: 720,
      fit: BoxFit.contain,
      alignment: Alignment.centerLeft,
      errorBuilder: (_, _, _) => Text(
        'PREDATOR GROUP',
        style: TextStyle(
          color: compact ? Colors.white : AppColors.primary,
          fontWeight: FontWeight.w900,
          letterSpacing: 0,
        ),
      ),
    );
  }
}

class CommerceSearchField extends StatelessWidget {
  const CommerceSearchField({
    this.controller,
    this.onChanged,
    this.onSubmitted,
    this.onTap,
    this.readOnly = false,
    this.autofocus = false,
    this.onClear,
    this.hintText = 'Tìm laptop, thương hiệu, cấu hình...',
    super.key,
  });

  final TextEditingController? controller;
  final ValueChanged<String>? onChanged;
  final ValueChanged<String>? onSubmitted;
  final VoidCallback? onTap;
  final bool readOnly;
  final bool autofocus;
  final VoidCallback? onClear;
  final String hintText;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 44,
      child: TextField(
        controller: controller,
        readOnly: readOnly,
        autofocus: autofocus,
        onTap: onTap,
        onChanged: onChanged,
        onSubmitted: onSubmitted,
        textInputAction: TextInputAction.search,
        decoration: InputDecoration(
          hintText: hintText,
          hintStyle: const TextStyle(fontSize: 13, color: AppColors.muted),
          prefixIcon: const Icon(Icons.search, size: 21),
          suffixIcon: controller == null
              ? null
              : ValueListenableBuilder<TextEditingValue>(
                  valueListenable: controller!,
                  builder: (context, value, _) => value.text.isEmpty
                      ? const SizedBox.shrink()
                      : IconButton(
                          tooltip: 'Xóa tìm kiếm',
                          onPressed: () {
                            controller!.clear();
                            onClear?.call();
                          },
                          icon: const Icon(Icons.close, size: 18),
                        ),
                ),
        ),
      ),
    );
  }
}

class SectionHeader extends StatelessWidget {
  const SectionHeader({
    required this.title,
    this.subtitle,
    this.actionLabel,
    this.onAction,
    super.key,
  });

  final String title;
  final String? subtitle;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: Theme.of(context).textTheme.titleLarge),
              if (subtitle != null) ...[
                const SizedBox(height: 3),
                Text(subtitle!, style: Theme.of(context).textTheme.bodySmall),
              ],
            ],
          ),
        ),
        if (actionLabel != null)
          TextButton(
            onPressed: onAction,
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(actionLabel!),
                const SizedBox(width: 2),
                const Icon(Icons.chevron_right, size: 18),
              ],
            ),
          ),
      ],
    );
  }
}

class ProductGridCard extends StatelessWidget {
  const ProductGridCard({
    required this.product,
    required this.onTap,
    super.key,
  });

  final Product product;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final status = product.status.trim();
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Stack(
                fit: StackFit.expand,
                children: [
                  ColoredBox(
                    color: const Color(0xFFF8FAFC),
                    child: Padding(
                      padding: const EdgeInsets.all(8),
                      child: NetworkImageBox(
                        url: product.imageUrl,
                        fit: BoxFit.contain,
                      ),
                    ),
                  ),
                  if (status.isNotEmpty)
                    Positioned(
                      left: 7,
                      top: 7,
                      child: StatusPill(
                        label: status.toUpperCase(),
                        color: _statusColor(status),
                      ),
                    ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(10, 9, 10, 10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (product.brandName.isNotEmpty)
                    Text(
                      product.brandName.toUpperCase(),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: AppColors.primary,
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  const SizedBox(height: 3),
                  Text(
                    product.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: AppColors.text,
                      fontSize: 13,
                      height: 1.25,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 7),
                  Text(
                    formatMoney(product.displayPrice),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: AppColors.danger,
                      fontSize: 14,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 4),
                  const Row(
                    children: [
                      Icon(
                        Icons.local_shipping_outlined,
                        size: 13,
                        color: AppColors.success,
                      ),
                      SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          'Freeship • Chính hãng',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 10,
                            color: AppColors.muted,
                          ),
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

  Color _statusColor(String value) {
    final normalized = value.toLowerCase();
    if (normalized.contains('hot') || normalized.contains('sale')) {
      return AppColors.danger;
    }
    if (normalized.contains('mới') || normalized.contains('new')) {
      return AppColors.primary;
    }
    return AppColors.success;
  }
}

class CategoryIconTile extends StatelessWidget {
  const CategoryIconTile({
    required this.category,
    required this.onTap,
    super.key,
  });

  final Category category;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final appearance = categoryAppearance(category.name);
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: SizedBox(
        width: 76,
        child: Column(
          children: [
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                color: appearance.color.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: appearance.color.withValues(alpha: 0.25),
                ),
              ),
              child: Icon(appearance.icon, color: appearance.color, size: 27),
            ),
            const SizedBox(height: 6),
            Text(
              category.name,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: AppColors.text,
                fontSize: 11,
                height: 1.15,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class StatusPill extends StatelessWidget {
  const StatusPill({
    required this.label,
    this.color = AppColors.primary,
    super.key,
  });

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
        child: Text(
          label,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 9,
            fontWeight: FontWeight.w900,
          ),
        ),
      ),
    );
  }
}

class QuantityStepper extends StatelessWidget {
  const QuantityStepper({
    required this.quantity,
    required this.onDecrease,
    required this.onIncrease,
    this.compact = false,
    super.key,
  });

  final int quantity;
  final VoidCallback? onDecrease;
  final VoidCallback? onIncrease;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final height = compact ? 32.0 : 38.0;
    return Container(
      height: height,
      decoration: BoxDecoration(
        color: AppColors.surface,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _button(Icons.remove, onDecrease, height),
          SizedBox(
            width: compact ? 34 : 42,
            child: Text(
              '$quantity',
              textAlign: TextAlign.center,
              style: const TextStyle(fontWeight: FontWeight.w800),
            ),
          ),
          _button(Icons.add, onIncrease, height),
        ],
      ),
    );
  }

  Widget _button(IconData icon, VoidCallback? onPressed, double height) {
    return SizedBox(
      width: height,
      height: height,
      child: IconButton(
        padding: EdgeInsets.zero,
        onPressed: onPressed,
        icon: Icon(icon, size: 17),
      ),
    );
  }
}

class CategoryAppearance {
  const CategoryAppearance(this.icon, this.color);

  final IconData icon;
  final Color color;
}

CategoryAppearance categoryAppearance(String name) {
  final value = name.toLowerCase();
  if (value.contains('gaming')) {
    return const CategoryAppearance(
      Icons.sports_esports_outlined,
      AppColors.danger,
    );
  }
  if (value.contains('mac') || value.contains('apple')) {
    return const CategoryAppearance(Icons.laptop_mac_outlined, AppColors.navy);
  }
  if (value.contains('workstation') || value.contains('đồ họa')) {
    return const CategoryAppearance(
      Icons.view_in_ar_outlined,
      Color(0xFF7C3AED),
    );
  }
  if (value.contains('văn phòng') || value.contains('office')) {
    return const CategoryAppearance(
      Icons.business_center_outlined,
      AppColors.success,
    );
  }
  if (value.contains('học') || value.contains('student')) {
    return const CategoryAppearance(Icons.school_outlined, AppColors.warning);
  }
  return const CategoryAppearance(
    Icons.laptop_windows_outlined,
    AppColors.primary,
  );
}
