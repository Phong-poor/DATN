import 'dart:async';

import 'package:flutter/material.dart';

import '../../../app/app_dependencies.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/models/category.dart';
import '../../../shared/models/product.dart';
import '../../../shared/widgets/commerce_widgets.dart';
import '../../../shared/widgets/state_content.dart';
import 'product_detail_screen.dart';

enum _ProductSort { featured, lowPrice, highPrice, name }

class ProductListScreen extends StatefulWidget {
  const ProductListScreen({this.category, this.autofocus = false, super.key});

  final Category? category;
  final bool autofocus;

  @override
  State<ProductListScreen> createState() => _ProductListScreenState();
}

class _ProductListScreenState extends State<ProductListScreen> {
  final _search = TextEditingController();
  Timer? _timer;
  bool _loading = true;
  String? _error;
  List<Product> _allItems = const [];
  List<Product> _items = const [];
  _ProductSort _sort = _ProductSort.featured;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_loading && _items.isEmpty && _error == null) _load();
  }

  @override
  void dispose() {
    _timer?.cancel();
    _search.dispose();
    super.dispose();
  }

  Future<void> _load([String query = '', bool refresh = false]) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final service = AppScope.of(context).productService;
      var products = query.trim().isEmpty
          ? await service.getProducts(refresh: refresh)
          : await service.search(query);
      if (widget.category != null) {
        products = products
            .where((product) => product.categoryId == widget.category!.id)
            .toList();
      }
      _allItems = products;
      _items = _sorted(_allItems);
    } catch (exception) {
      _error = '$exception';
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  List<Product> _sorted(List<Product> products) {
    final result = [...products];
    switch (_sort) {
      case _ProductSort.featured:
        return result;
      case _ProductSort.lowPrice:
        result.sort((a, b) => a.displayPrice.compareTo(b.displayPrice));
      case _ProductSort.highPrice:
        result.sort((a, b) => b.displayPrice.compareTo(a.displayPrice));
      case _ProductSort.name:
        result.sort((a, b) => a.name.compareTo(b.name));
    }
    return result;
  }

  void _onSearch(String value) {
    _timer?.cancel();
    _timer = Timer(const Duration(milliseconds: 450), () => _load(value));
  }

  void _changeSort(_ProductSort value) {
    setState(() {
      _sort = value;
      _items = _sorted(_allItems);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.category?.name ?? 'Tất cả sản phẩm'),
        actions: [
          IconButton(
            tooltip: 'Tải lại',
            onPressed: () => _load(_search.text, true),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: Column(
        children: [
          ColoredBox(
            color: AppColors.surface,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(14, 4, 14, 12),
              child: CommerceSearchField(
                controller: _search,
                autofocus: widget.autofocus,
                onChanged: _onSearch,
                onSubmitted: _load,
                onClear: _load,
              ),
            ),
          ),
          Container(
            color: AppColors.surface,
            width: double.infinity,
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _SortChip(
                    label: 'Nổi bật',
                    selected: _sort == _ProductSort.featured,
                    onTap: () => _changeSort(_ProductSort.featured),
                  ),
                  _SortChip(
                    label: 'Giá thấp',
                    selected: _sort == _ProductSort.lowPrice,
                    onTap: () => _changeSort(_ProductSort.lowPrice),
                  ),
                  _SortChip(
                    label: 'Giá cao',
                    selected: _sort == _ProductSort.highPrice,
                    onTap: () => _changeSort(_ProductSort.highPrice),
                  ),
                  _SortChip(
                    label: 'Tên A-Z',
                    selected: _sort == _ProductSort.name,
                    onTap: () => _changeSort(_ProductSort.name),
                  ),
                ],
              ),
            ),
          ),
          Expanded(
            child: StateContent(
              loading: _loading,
              error: _error,
              empty: !_loading && _items.isEmpty,
              emptyMessage: 'Không tìm thấy sản phẩm phù hợp',
              onRetry: () => _load(_search.text),
              child: RefreshIndicator(
                onRefresh: () => _load(_search.text, true),
                child: GridView.builder(
                  padding: const EdgeInsets.all(14),
                  keyboardDismissBehavior:
                      ScrollViewKeyboardDismissBehavior.onDrag,
                  itemCount: _items.length,
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    mainAxisSpacing: 10,
                    crossAxisSpacing: 10,
                    mainAxisExtent: 274,
                  ),
                  itemBuilder: (context, index) {
                    final product = _items[index];
                    return ProductGridCard(
                      product: product,
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (_) =>
                              ProductDetailScreen(productId: product.id),
                        ),
                      ),
                    );
                  },
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SortChip extends StatelessWidget {
  const _SortChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => onTap(),
        labelStyle: TextStyle(
          color: selected ? AppColors.primary : AppColors.text,
          fontSize: 12,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
