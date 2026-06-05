import 'package:flutter/material.dart';

import '../../../app/app_dependencies.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/product.dart';
import '../../../shared/widgets/commerce_widgets.dart';
import '../../../shared/widgets/network_image_box.dart';
import '../../../shared/widgets/state_content.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../auth/presentation/login_screen.dart';
import '../../cart/presentation/cart_screen.dart';

class ProductDetailScreen extends StatefulWidget {
  const ProductDetailScreen({required this.productId, super.key});

  final int productId;

  @override
  State<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends State<ProductDetailScreen> {
  bool _loading = true;
  bool _adding = false;
  bool _savingWishlist = false;
  bool _wishlistSaved = false;
  String? _error;
  Product? _product;
  int? _variantId;
  int _quantity = 1;
  int _imageIndex = 0;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_loading && _product == null && _error == null) _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final product = await AppScope.of(
        context,
      ).productService.getProduct(widget.productId);
      _product = product;
      _variantId = product.firstAvailableVariant?.id;
    } catch (exception) {
      _error = '$exception';
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  ProductVariant? get _selectedVariant {
    final variants = _product?.variants ?? const <ProductVariant>[];
    for (final variant in variants) {
      if (variant.id == _variantId) return variant;
    }
    return null;
  }

  List<String> get _images {
    final product = _product;
    if (product == null) return const [];
    return <String>{
      product.imageUrl,
      ...product.images.map((item) => item.url),
    }.where((url) => url.isNotEmpty).toList();
  }

  Future<bool> _ensureLogin() async {
    final auth = AppScope.of(context).authController;
    if (auth.status == AuthStatus.authenticated) return true;
    await Navigator.of(
      context,
    ).push(MaterialPageRoute<void>(builder: (_) => const LoginScreen()));
    return mounted && auth.status == AuthStatus.authenticated;
  }

  Future<void> _addToCart({bool openCart = false}) async {
    final cartService = AppScope.of(context).cartService;
    if (_variantId == null || !await _ensureLogin()) return;
    setState(() => _adding = true);
    try {
      final message = await cartService.add(
        variantId: _variantId!,
        quantity: _quantity,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(message.isEmpty ? 'Đã thêm vào giỏ hàng' : message),
        ),
      );
      if (openCart) {
        await Navigator.of(
          context,
        ).push(MaterialPageRoute<void>(builder: (_) => const CartScreen()));
      }
    } catch (exception) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('$exception')));
      }
    } finally {
      if (mounted) setState(() => _adding = false);
    }
  }

  Future<void> _openCart() async {
    if (!await _ensureLogin() || !mounted) return;
    await Navigator.of(
      context,
    ).push(MaterialPageRoute<void>(builder: (_) => const CartScreen()));
  }

  Future<void> _addToWishlist() async {
    if (_variantId == null || !await _ensureLogin()) return;
    if (!mounted) return;
    final service = AppScope.of(context).wishlistService;
    setState(() => _savingWishlist = true);
    try {
      final message = await service.add(variantId: _variantId!);
      if (!mounted) return;
      setState(() => _wishlistSaved = true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            message.isEmpty ? 'Đã thêm vào danh sách yêu thích' : message,
          ),
        ),
      );
    } catch (exception) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('$exception')));
      }
    } finally {
      if (mounted) setState(() => _savingWishlist = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final product = _product;
    final variant = _selectedVariant;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Chi tiết sản phẩm'),
        actions: [
          IconButton(
            tooltip: 'Thêm vào yêu thích',
            onPressed: _savingWishlist || _variantId == null
                ? null
                : _addToWishlist,
            icon: Icon(
              _wishlistSaved ? Icons.favorite : Icons.favorite_border,
              color: _wishlistSaved ? AppColors.danger : null,
            ),
          ),
          IconButton(
            tooltip: 'Giỏ hàng',
            onPressed: _openCart,
            icon: const Icon(Icons.shopping_cart_outlined),
          ),
        ],
      ),
      body: StateContent(
        loading: _loading,
        error: _error,
        onRetry: _load,
        empty: !_loading && product == null,
        child: product == null
            ? const SizedBox.shrink()
            : ListView(
                padding: const EdgeInsets.only(bottom: 24),
                children: [
                  _ProductGallery(
                    images: _images,
                    index: _imageIndex,
                    onChanged: (value) => setState(() => _imageIndex = value),
                  ),
                  _ProductSummary(product: product, variant: variant),
                  const SizedBox(height: 8),
                  _VariantSelector(
                    variants: product.variants,
                    selectedId: _variantId,
                    onSelected: (id) => setState(() {
                      _variantId = id;
                      _quantity = 1;
                    }),
                  ),
                  const SizedBox(height: 8),
                  _QuantitySection(
                    quantity: _quantity,
                    stock: variant?.stock ?? 0,
                    onDecrease: _quantity > 1
                        ? () => setState(() => _quantity--)
                        : null,
                    onIncrease: variant != null && _quantity < variant.stock
                        ? () => setState(() => _quantity++)
                        : null,
                  ),
                  const SizedBox(height: 8),
                  const _ProductBenefits(),
                  if (product.specifications.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    _SpecificationSection(items: product.specifications),
                  ],
                ],
              ),
      ),
      bottomNavigationBar: product == null
          ? null
          : SafeArea(
              top: false,
              child: Container(
                padding: const EdgeInsets.fromLTRB(10, 9, 10, 9),
                decoration: const BoxDecoration(
                  color: AppColors.surface,
                  border: Border(top: BorderSide(color: AppColors.border)),
                ),
                child: Row(
                  children: [
                    SizedBox(
                      width: 52,
                      height: 50,
                      child: OutlinedButton(
                        onPressed: _adding ? null : _openCart,
                        style: OutlinedButton.styleFrom(
                          padding: EdgeInsets.zero,
                        ),
                        child: const Icon(Icons.shopping_cart_outlined),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _adding || variant == null
                            ? null
                            : _addToCart,
                        child: const Text(
                          'Thêm vào giỏ',
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: FilledButton(
                        onPressed: _adding || variant == null
                            ? null
                            : () => _addToCart(openCart: true),
                        child: Text(_adding ? 'Đang thêm...' : 'Mua ngay'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}

class _ProductGallery extends StatelessWidget {
  const _ProductGallery({
    required this.images,
    required this.index,
    required this.onChanged,
  });

  final List<String> images;
  final int index;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 325,
      color: AppColors.surface,
      child: Stack(
        children: [
          PageView.builder(
            itemCount: images.isEmpty ? 1 : images.length,
            onPageChanged: onChanged,
            itemBuilder: (_, imageIndex) => Padding(
              padding: const EdgeInsets.all(22),
              child: images.isEmpty
                  ? const NetworkImageBox(url: '', fit: BoxFit.contain)
                  : NetworkImageBox(
                      url: images[imageIndex],
                      fit: BoxFit.contain,
                    ),
            ),
          ),
          if (images.length > 1)
            Positioned(
              right: 12,
              bottom: 10,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.navy.withValues(alpha: 0.8),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  '${index + 1}/${images.length}',
                  style: const TextStyle(color: Colors.white, fontSize: 11),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _ProductSummary extends StatelessWidget {
  const _ProductSummary({required this.product, required this.variant});

  final Product product;
  final ProductVariant? variant;

  @override
  Widget build(BuildContext context) {
    final stock = variant?.stock;
    return Container(
      color: AppColors.surface,
      padding: const EdgeInsets.fromLTRB(14, 16, 14, 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              if (product.status.isNotEmpty)
                StatusPill(
                  label: product.status.toUpperCase(),
                  color: AppColors.danger,
                ),
              if (product.status.isNotEmpty) const SizedBox(width: 7),
              Expanded(
                child: Text(
                  product.brandName.isEmpty
                      ? 'PREDATOR GROUP'
                      : product.brandName.toUpperCase(),
                  style: const TextStyle(
                    color: AppColors.primary,
                    fontSize: 11,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              Text(
                product.sku,
                style: const TextStyle(color: AppColors.muted, fontSize: 11),
              ),
            ],
          ),
          const SizedBox(height: 9),
          Text(product.name, style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 12),
          Text(
            formatMoney(variant?.price ?? product.displayPrice),
            style: const TextStyle(
              color: AppColors.danger,
              fontSize: 24,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              const Icon(Icons.star, color: AppColors.warning, size: 17),
              const SizedBox(width: 4),
              const Text('5.0', style: TextStyle(fontWeight: FontWeight.w800)),
              const SizedBox(width: 12),
              Text(
                stock == null
                    ? 'Chưa có biến thể'
                    : stock > 0
                    ? 'Còn $stock sản phẩm'
                    : 'Hết hàng',
                style: TextStyle(
                  color: stock == 0 ? AppColors.danger : AppColors.success,
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _VariantSelector extends StatelessWidget {
  const _VariantSelector({
    required this.variants,
    required this.selectedId,
    required this.onSelected,
  });

  final List<ProductVariant> variants;
  final int? selectedId;
  final ValueChanged<int> onSelected;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.surface,
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Chọn phiên bản',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 10),
          if (variants.isEmpty)
            const Text('Sản phẩm chưa có phiên bản để mua')
          else
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: variants
                  .map(
                    (variant) => ChoiceChip(
                      selected: variant.id == selectedId,
                      onSelected: variant.stock > 0
                          ? (_) => onSelected(variant.id)
                          : null,
                      label: ConstrainedBox(
                        constraints: const BoxConstraints(maxWidth: 230),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              variant.name.isEmpty
                                  ? 'Phiên bản #${variant.id}'
                                  : variant.name,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            Text(
                              formatMoney(variant.price),
                              style: const TextStyle(
                                color: AppColors.danger,
                                fontSize: 11,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  )
                  .toList(),
            ),
        ],
      ),
    );
  }
}

class _QuantitySection extends StatelessWidget {
  const _QuantitySection({
    required this.quantity,
    required this.stock,
    required this.onDecrease,
    required this.onIncrease,
  });

  final int quantity;
  final int stock;
  final VoidCallback? onDecrease;
  final VoidCallback? onIncrease;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.surface,
      padding: const EdgeInsets.all(14),
      child: Row(
        children: [
          Expanded(
            child: Text(
              'Số lượng',
              style: Theme.of(context).textTheme.titleMedium,
            ),
          ),
          QuantityStepper(
            quantity: quantity,
            onDecrease: onDecrease,
            onIncrease: onIncrease,
          ),
        ],
      ),
    );
  }
}

class _ProductBenefits extends StatelessWidget {
  const _ProductBenefits();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.surface,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 15),
      child: const Column(
        children: [
          _BenefitRow(
            Icons.verified_user_outlined,
            'Bảo hành chính hãng 24 tháng',
          ),
          SizedBox(height: 12),
          _BenefitRow(Icons.cached_outlined, 'Đổi trả miễn phí trong 7 ngày'),
          SizedBox(height: 12),
          _BenefitRow(Icons.local_shipping_outlined, 'Giao nhanh toàn quốc'),
        ],
      ),
    );
  }
}

class _BenefitRow extends StatelessWidget {
  const _BenefitRow(this.icon, this.label);

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, color: AppColors.primary, size: 21),
        const SizedBox(width: 10),
        Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
      ],
    );
  }
}

class _SpecificationSection extends StatelessWidget {
  const _SpecificationSection({required this.items});

  final List<Map<String, dynamic>> items;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.surface,
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Thông số kỹ thuật',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 10),
          for (var index = 0; index < items.length; index++) ...[
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  flex: 2,
                  child: Text(
                    '${items[index]['ten'] ?? items[index]['name'] ?? items[index]['ten_thuoctinh'] ?? ''}',
                    style: const TextStyle(color: AppColors.muted),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  flex: 3,
                  child: Text(
                    '${items[index]['giatri'] ?? items[index]['value'] ?? ''}',
                    textAlign: TextAlign.right,
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                ),
              ],
            ),
            if (index != items.length - 1)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 10),
                child: Divider(),
              ),
          ],
        ],
      ),
    );
  }
}
