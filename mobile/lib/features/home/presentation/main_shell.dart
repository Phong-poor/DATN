import 'package:flutter/material.dart';

import '../../../app/app_dependencies.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../auth/presentation/login_screen.dart';
import '../../cart/presentation/cart_screen.dart';
import '../../categories/presentation/category_screen.dart';
import '../../products/presentation/product_list_screen.dart';
import '../../profile/presentation/profile_screen.dart';
import 'home_screen.dart';

class MainShell extends StatefulWidget {
  const MainShell({required this.authenticated, super.key});

  final bool authenticated;

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _index = 0;
  final Set<int> _loadedIndexes = {0};
  late final List<Widget> _screens;

  @override
  void initState() {
    super.initState();
    _screens = const [
      HomeScreen(),
      CategoryScreen(),
      ProductListScreen(),
      CartScreen(),
      ProfileScreen(),
    ];
  }

  @override
  void didUpdateWidget(MainShell oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.authenticated && !widget.authenticated && _index >= 3) {
      _index = 0;
      _loadedIndexes.removeAll({3, 4});
    }
  }

  Future<void> _select(int value) async {
    if (value >= 3 && !widget.authenticated) {
      final auth = AppScope.of(context).authController;
      await Navigator.of(
        context,
      ).push(MaterialPageRoute<void>(builder: (_) => const LoginScreen()));
      if (!mounted || auth.status != AuthStatus.authenticated) return;
    }
    if (mounted) {
      setState(() {
        _index = value;
        _loadedIndexes.add(value);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBody: true,
      body: Padding(
        padding: const EdgeInsets.only(bottom: 86),
        child: IndexedStack(
          index: _index,
          children: List.generate(
            _screens.length,
            (index) => _loadedIndexes.contains(index)
                ? _screens[index]
                : const SizedBox.shrink(),
          ),
        ),
      ),
      bottomNavigationBar: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
          child: Container(
            decoration: BoxDecoration(
              color: AppColors.surface.withValues(alpha: 0.96),
              borderRadius: BorderRadius.circular(22),
              border: Border.all(
                color: AppColors.border.withValues(alpha: 0.75),
              ),
              boxShadow: [
                BoxShadow(
                  color: AppColors.navy.withValues(alpha: 0.12),
                  blurRadius: 22,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(22),
              child: NavigationBar(
                selectedIndex: _index,
                onDestinationSelected: _select,
                destinations: const [
                  NavigationDestination(
                    icon: Icon(Icons.home_outlined),
                    selectedIcon: Icon(Icons.home),
                    label: 'Trang chủ',
                  ),
                  NavigationDestination(
                    icon: Icon(Icons.grid_view_outlined),
                    selectedIcon: Icon(Icons.grid_view),
                    label: 'Danh mục',
                  ),
                  NavigationDestination(
                    icon: Icon(Icons.devices_outlined),
                    selectedIcon: Icon(Icons.devices),
                    label: 'Sản phẩm',
                  ),
                  NavigationDestination(
                    icon: Icon(Icons.shopping_cart_outlined),
                    selectedIcon: Icon(Icons.shopping_cart),
                    label: 'Giỏ hàng',
                  ),
                  NavigationDestination(
                    icon: Icon(Icons.person_outline),
                    selectedIcon: Icon(Icons.person),
                    label: 'Tài khoản',
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
