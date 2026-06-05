import 'package:flutter/material.dart';

import '../../../app/app_dependencies.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/models/category.dart';
import '../../../shared/widgets/commerce_widgets.dart';
import '../../../shared/widgets/state_content.dart';
import '../../products/presentation/product_list_screen.dart';

class CategoryScreen extends StatefulWidget {
  const CategoryScreen({super.key});

  @override
  State<CategoryScreen> createState() => _CategoryScreenState();
}

class _CategoryScreenState extends State<CategoryScreen> {
  bool _loading = true;
  String? _error;
  List<Category> _items = const [];

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_loading && _items.isEmpty && _error == null) _load();
  }

  Future<void> _load({bool refresh = false}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      _items = await AppScope.of(
        context,
      ).categoryService.getCategories(refresh: refresh);
    } catch (exception) {
      _error = '$exception';
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Danh mục sản phẩm')),
      body: StateContent(
        loading: _loading,
        error: _error,
        empty: !_loading && _items.isEmpty,
        onRetry: _load,
        child: RefreshIndicator(
          onRefresh: () => _load(refresh: true),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(14, 6, 14, 24),
            children: [
              const _CategoryBanner(),
              const SizedBox(height: 20),
              const SectionHeader(
                title: 'Mua sắm theo nhu cầu',
                subtitle: 'Chọn nhóm sản phẩm phù hợp với bạn',
              ),
              const SizedBox(height: 12),
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: _items.length,
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  mainAxisSpacing: 10,
                  crossAxisSpacing: 10,
                  mainAxisExtent: 132,
                ),
                itemBuilder: (context, index) {
                  final category = _items[index];
                  final appearance = categoryAppearance(category.name);
                  return Card(
                    clipBehavior: Clip.antiAlias,
                    child: InkWell(
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (_) => ProductListScreen(category: category),
                        ),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(14),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              width: 42,
                              height: 42,
                              decoration: BoxDecoration(
                                color: appearance.color.withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Icon(
                                appearance.icon,
                                color: appearance.color,
                              ),
                            ),
                            const Spacer(),
                            Text(
                              category.name,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: Theme.of(context).textTheme.titleSmall,
                            ),
                            const SizedBox(height: 2),
                            const Text(
                              'Xem sản phẩm',
                              style: TextStyle(
                                color: AppColors.muted,
                                fontSize: 11,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CategoryBanner extends StatelessWidget {
  const _CategoryBanner();

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 142,
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: AppColors.navy,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Stack(
        children: [
          Positioned(
            right: -24,
            bottom: -18,
            width: 190,
            child: Image.asset(
              'assets/images/hero_gaming.png',
              cacheWidth: 640,
            ),
          ),
          const Padding(
            padding: EdgeInsets.all(16),
            child: SizedBox(
              width: 170,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    'TÌM ĐÚNG CỖ MÁY',
                    style: TextStyle(
                      color: AppColors.primary,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  SizedBox(height: 5),
                  Text(
                    'Chọn theo nhu cầu của bạn',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 19,
                      height: 1.1,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  SizedBox(height: 7),
                  Text(
                    'Gaming, đồ họa, văn phòng và học tập.',
                    style: TextStyle(color: Color(0xFFCBD5E1), fontSize: 11),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
