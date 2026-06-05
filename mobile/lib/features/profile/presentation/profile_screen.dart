import 'package:flutter/material.dart';

import '../../../app/app_dependencies.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/models/address.dart';
import '../../../shared/models/user.dart';
import '../../../shared/widgets/state_content.dart';
import '../../explore/presentation/chat_assistant_screen.dart';
import '../../explore/presentation/contact_screen.dart';
import '../../explore/presentation/news_screen.dart';
import '../../explore/presentation/promotions_screen.dart';
import '../../orders/presentation/order_history_screen.dart';
import '../../wishlist/presentation/wishlist_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  bool _loading = true;
  bool _saving = false;
  String? _error;
  User? _user;
  List<Address> _addresses = const [];

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_loading && _user == null && _error == null) _load();
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final service = AppScope.of(context).profileService;
      final values = await Future.wait([
        service.getProfile(),
        service.getAddresses(),
      ]);
      _user = values[0] as User;
      _addresses = values[1] as List<Address>;
      _name.text = _user!.name;
      _email.text = _user!.email;
      _phone.text = _user!.phone;
    } catch (exception) {
      _error = '$exception';
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    final deps = AppScope.of(context);
    setState(() => _saving = true);
    try {
      final user = await deps.profileService.updateProfile(
        name: _name.text,
        email: _email.text,
        phone: _phone.text,
      );
      deps.authController.updateUser(user);
      _user = user;
      _show('Đã cập nhật hồ sơ');
    } catch (exception) {
      _show(exception);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _deleteAddress(Address address) async {
    try {
      await AppScope.of(context).profileService.deleteAddress(address.id);
      await _load();
    } catch (exception) {
      _show(exception);
    }
  }

  Future<void> _defaultAddress(Address address) async {
    try {
      await AppScope.of(context).profileService.setDefaultAddress(address.id);
      await _load();
    } catch (exception) {
      _show(exception);
    }
  }

  Future<void> _editAddress([Address? address]) async {
    final result = await showDialog<bool>(
      context: context,
      builder: (_) => _AddressDialog(address: address),
    );
    if (result == true) await _load();
  }

  void _show(Object message) {
    if (!mounted) return;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text('$message')));
  }

  @override
  Widget build(BuildContext context) {
    final auth = AppScope.of(context).authController;
    return Scaffold(
      body: StateContent(
        loading: _loading,
        error: _error,
        onRetry: _load,
        empty: !_loading && _user == null,
        child: RefreshIndicator(
          onRefresh: _load,
          child: Form(
            key: _formKey,
            child: ListView(
              padding: EdgeInsets.zero,
              children: [
                _ProfileHeader(user: _user, onLogout: auth.logout),
                Padding(
                  padding: const EdgeInsets.fromLTRB(12, 12, 12, 24),
                  child: Column(
                    children: [
                      _OrderShortcut(
                        onTap: () => Navigator.of(context).push(
                          MaterialPageRoute<void>(
                            builder: (_) => const OrderHistoryScreen(),
                          ),
                        ),
                      ),
                      const SizedBox(height: 10),
                      const _AccountUtilities(),
                      const SizedBox(height: 10),
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(14),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Thông tin tài khoản',
                                style: Theme.of(context).textTheme.titleMedium,
                              ),
                              const SizedBox(height: 14),
                              TextFormField(
                                controller: _name,
                                decoration: const InputDecoration(
                                  labelText: 'Họ tên',
                                  prefixIcon: Icon(Icons.person_outline),
                                ),
                                validator: _required,
                              ),
                              const SizedBox(height: 10),
                              TextFormField(
                                controller: _email,
                                decoration: const InputDecoration(
                                  labelText: 'Email',
                                  prefixIcon: Icon(Icons.email_outlined),
                                ),
                                validator: _required,
                              ),
                              const SizedBox(height: 10),
                              TextFormField(
                                controller: _phone,
                                keyboardType: TextInputType.phone,
                                decoration: const InputDecoration(
                                  labelText: 'Số điện thoại',
                                  prefixIcon: Icon(Icons.phone_outlined),
                                ),
                              ),
                              const SizedBox(height: 12),
                              SizedBox(
                                width: double.infinity,
                                child: FilledButton.icon(
                                  onPressed: _saving ? null : _save,
                                  icon: const Icon(Icons.save_outlined),
                                  label: Text(
                                    _saving ? 'Đang lưu...' : 'Lưu thay đổi',
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 10),
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(14),
                          child: Column(
                            children: [
                              Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      'Địa chỉ giao hàng',
                                      style: Theme.of(
                                        context,
                                      ).textTheme.titleMedium,
                                    ),
                                  ),
                                  IconButton(
                                    tooltip: 'Thêm địa chỉ',
                                    onPressed: _editAddress,
                                    icon: const Icon(
                                      Icons.add_location_alt_outlined,
                                    ),
                                  ),
                                ],
                              ),
                              if (_addresses.isEmpty)
                                const Padding(
                                  padding: EdgeInsets.symmetric(vertical: 18),
                                  child: Text('Chưa có địa chỉ đã lưu'),
                                ),
                              for (final address in _addresses) ...[
                                const Divider(),
                                ListTile(
                                  contentPadding: EdgeInsets.zero,
                                  leading: Icon(
                                    address.isDefault
                                        ? Icons.home
                                        : Icons.location_on_outlined,
                                    color: address.isDefault
                                        ? AppColors.primary
                                        : AppColors.muted,
                                  ),
                                  title: Text(
                                    address.fullAddress,
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                  subtitle: Text(
                                    address.isDefault
                                        ? 'Địa chỉ mặc định'
                                        : address.type,
                                  ),
                                  onTap: () => _editAddress(address),
                                  trailing: PopupMenuButton<String>(
                                    onSelected: (value) {
                                      if (value == 'default') {
                                        _defaultAddress(address);
                                      }
                                      if (value == 'delete') {
                                        _deleteAddress(address);
                                      }
                                    },
                                    itemBuilder: (_) => const [
                                      PopupMenuItem(
                                        value: 'default',
                                        child: Text('Đặt mặc định'),
                                      ),
                                      PopupMenuItem(
                                        value: 'delete',
                                        child: Text('Xóa'),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String? _required(String? value) =>
      value?.trim().isNotEmpty == true ? null : 'Bắt buộc';
}

class _AccountUtilities extends StatelessWidget {
  const _AccountUtilities();

  @override
  Widget build(BuildContext context) {
    final items = <({IconData icon, String label, Color color, Widget screen})>[
      (
        icon: Icons.favorite_border,
        label: 'Yêu thích',
        color: AppColors.danger,
        screen: const WishlistScreen(),
      ),
      (
        icon: Icons.local_offer_outlined,
        label: 'Khuyến mãi',
        color: AppColors.warning,
        screen: const PromotionsScreen(),
      ),
      (
        icon: Icons.newspaper_outlined,
        label: 'Tin tức',
        color: AppColors.primary,
        screen: const NewsScreen(),
      ),
      (
        icon: Icons.auto_awesome_outlined,
        label: 'Trợ lý AI',
        color: const Color(0xFF7C3AED),
        screen: const ChatAssistantScreen(),
      ),
      (
        icon: Icons.support_agent_outlined,
        label: 'Liên hệ',
        color: AppColors.success,
        screen: const ContactScreen(),
      ),
    ];
    return Card(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 10),
        child: GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 4,
            mainAxisExtent: 76,
          ),
          itemCount: items.length,
          itemBuilder: (context, index) {
            final item = items[index];
            return InkWell(
              borderRadius: BorderRadius.circular(8),
              onTap: () => Navigator.of(
                context,
              ).push(MaterialPageRoute<void>(builder: (_) => item.screen)),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(item.icon, color: item.color, size: 24),
                  const SizedBox(height: 6),
                  Text(
                    item.label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}

class _ProfileHeader extends StatelessWidget {
  const _ProfileHeader({required this.user, required this.onLogout});

  final User? user;
  final VoidCallback onLogout;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.navy,
      padding: EdgeInsets.fromLTRB(
        16,
        MediaQuery.paddingOf(context).top + 18,
        10,
        22,
      ),
      child: Row(
        children: [
          Container(
            width: 60,
            height: 60,
            decoration: BoxDecoration(
              color: AppColors.primary,
              borderRadius: BorderRadius.circular(8),
            ),
            alignment: Alignment.center,
            child: Text(
              (user?.name.isNotEmpty == true ? user!.name[0] : 'P')
                  .toUpperCase(),
              style: const TextStyle(
                color: Colors.white,
                fontSize: 26,
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
                  user?.name ?? 'Tài khoản',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  user?.email ?? '',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Color(0xFFCBD5E1),
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
          IconButton(
            tooltip: 'Đăng xuất',
            onPressed: onLogout,
            icon: const Icon(Icons.logout, color: Colors.white),
          ),
        ],
      ),
    );
  }
}

class _OrderShortcut extends StatelessWidget {
  const _OrderShortcut({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        onTap: onTap,
        child: const Padding(
          padding: EdgeInsets.all(14),
          child: Row(
            children: [
              Icon(Icons.receipt_long_outlined, color: AppColors.primary),
              SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Đơn mua',
                      style: TextStyle(fontWeight: FontWeight.w800),
                    ),
                    SizedBox(height: 2),
                    Text(
                      'Theo dõi và quản lý đơn hàng',
                      style: TextStyle(color: AppColors.muted, fontSize: 11),
                    ),
                  ],
                ),
              ),
              Icon(Icons.chevron_right),
            ],
          ),
        ),
      ),
    );
  }
}

class _AddressDialog extends StatefulWidget {
  const _AddressDialog({this.address});

  final Address? address;

  @override
  State<_AddressDialog> createState() => _AddressDialogState();
}

class _AddressDialogState extends State<_AddressDialog> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _city;
  late final TextEditingController _district;
  late final TextEditingController _ward;
  late final TextEditingController _detail;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _city = TextEditingController(text: widget.address?.city);
    _district = TextEditingController(text: widget.address?.district);
    _ward = TextEditingController(text: widget.address?.ward);
    _detail = TextEditingController(text: widget.address?.detail);
  }

  @override
  void dispose() {
    _city.dispose();
    _district.dispose();
    _ward.dispose();
    _detail.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      await AppScope.of(context).profileService.saveAddress(
        id: widget.address?.id,
        city: _city.text,
        district: _district.text,
        ward: _ward.text,
        detail: _detail.text,
        type: widget.address?.type.isNotEmpty == true
            ? widget.address!.type
            : 'home',
        isDefault: widget.address?.isDefault ?? false,
      );
      if (mounted) Navigator.of(context).pop(true);
    } catch (exception) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('$exception')));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text(widget.address == null ? 'Thêm địa chỉ' : 'Sửa địa chỉ'),
      content: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              _field(_city, 'Tỉnh/Thành phố'),
              _field(_district, 'Quận/Huyện'),
              _field(_ward, 'Phường/Xã'),
              _field(_detail, 'Địa chỉ cụ thể'),
            ],
          ),
        ),
      ),
      actions: [
        TextButton(
          onPressed: _saving ? null : () => Navigator.of(context).pop(false),
          child: const Text('Hủy'),
        ),
        FilledButton(
          onPressed: _saving ? null : _save,
          child: const Text('Lưu'),
        ),
      ],
    );
  }

  Widget _field(TextEditingController controller, String label) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: TextFormField(
      controller: controller,
      decoration: InputDecoration(labelText: label),
      validator: (value) =>
          value?.trim().isNotEmpty == true ? null : 'Bắt buộc',
    ),
  );
}
