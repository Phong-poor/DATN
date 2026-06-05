import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../app/app_dependencies.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/address.dart';
import '../../../shared/models/cart.dart';
import '../../orders/presentation/order_history_screen.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _manualAddress = TextEditingController();
  final _promo = TextEditingController();
  final _freeShip = TextEditingController();
  bool _loading = true;
  bool _submitting = false;
  String? _error;
  List<Address> _addresses = const [];
  Cart? _cart;
  int? _addressId;
  String _payment = 'COD';

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_loading && _addresses.isEmpty && _error == null) _load();
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _manualAddress.dispose();
    _promo.dispose();
    _freeShip.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    final deps = AppScope.of(context);
    _name.text = deps.authController.user?.name ?? '';
    _phone.text = deps.authController.user?.phone ?? '';
    try {
      final values = await Future.wait([
        deps.profileService.getAddresses(),
        deps.cartService.getCart(),
      ]);
      _addresses = values[0] as List<Address>;
      _cart = values[1] as Cart;
      for (final address in _addresses) {
        if (address.isDefault) {
          _addressId = address.id;
          break;
        }
      }
    } catch (exception) {
      _error = '$exception';
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);
    try {
      final result = await AppScope.of(context).orderService.checkout(
        addressId: _addressId,
        address: _manualAddress.text,
        paymentMethod: _payment,
        name: _name.text,
        phone: _phone.text,
        promoCode: _promo.text,
        freeShipCode: _freeShip.text,
      );
      if (!mounted) return;
      await showDialog<void>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Đặt hàng thành công'),
          content: Text('Mã đơn hàng: #${result.order.id}'),
          actions: [
            FilledButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Xem đơn hàng'),
            ),
          ],
        ),
      );
      if (!mounted) return;
      if (result.paymentUrl.isNotEmpty) {
        await launchUrl(
          Uri.parse(result.paymentUrl),
          mode: LaunchMode.externalApplication,
        );
      }
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute<void>(builder: (_) => const OrderHistoryScreen()),
      );
    } catch (exception) {
      if (mounted) setState(() => _error = '$exception');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Thanh toán')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(12, 8, 12, 24),
                children: [
                  _CheckoutSection(
                    icon: Icons.location_on_outlined,
                    title: 'Địa chỉ nhận hàng',
                    child: Column(
                      children: [
                        TextFormField(
                          controller: _name,
                          decoration: const InputDecoration(
                            labelText: 'Người nhận',
                          ),
                          validator: _required,
                        ),
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: _phone,
                          keyboardType: TextInputType.phone,
                          decoration: const InputDecoration(
                            labelText: 'Số điện thoại',
                          ),
                          validator: _required,
                        ),
                        const SizedBox(height: 10),
                        DropdownButtonFormField<int?>(
                          initialValue: _addressId,
                          decoration: const InputDecoration(
                            labelText: 'Địa chỉ đã lưu',
                          ),
                          items: [
                            const DropdownMenuItem<int?>(
                              value: null,
                              child: Text('Nhập địa chỉ khác'),
                            ),
                            ..._addresses.map(
                              (address) => DropdownMenuItem<int?>(
                                value: address.id,
                                child: Text(
                                  address.fullAddress,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ),
                          ],
                          onChanged: (value) =>
                              setState(() => _addressId = value),
                        ),
                        if (_addressId == null) ...[
                          const SizedBox(height: 10),
                          TextFormField(
                            controller: _manualAddress,
                            minLines: 2,
                            maxLines: 3,
                            decoration: const InputDecoration(
                              labelText: 'Địa chỉ giao hàng',
                            ),
                            validator: _required,
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 10),
                  _CheckoutSection(
                    icon: Icons.payments_outlined,
                    title: 'Phương thức thanh toán',
                    child: SegmentedButton<String>(
                      segments: const [
                        ButtonSegment(value: 'COD', label: Text('COD')),
                        ButtonSegment(value: 'VNPay', label: Text('VNPay')),
                        ButtonSegment(value: 'MoMo', label: Text('MoMo')),
                      ],
                      selected: {_payment},
                      onSelectionChanged: (value) =>
                          setState(() => _payment = value.first),
                    ),
                  ),
                  const SizedBox(height: 10),
                  _CheckoutSection(
                    icon: Icons.confirmation_number_outlined,
                    title: 'Ưu đãi',
                    child: Column(
                      children: [
                        TextFormField(
                          controller: _promo,
                          decoration: const InputDecoration(
                            labelText: 'Mã giảm giá',
                            prefixIcon: Icon(Icons.sell_outlined),
                          ),
                        ),
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: _freeShip,
                          decoration: const InputDecoration(
                            labelText: 'Mã freeship',
                            prefixIcon: Icon(Icons.local_shipping_outlined),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 10),
                  _CheckoutSection(
                    icon: Icons.receipt_long_outlined,
                    title: 'Chi tiết thanh toán',
                    child: Column(
                      children: [
                        _PriceRow('Tạm tính', formatMoney(_cart?.total ?? 0)),
                        const SizedBox(height: 8),
                        const _PriceRow('Phí vận chuyển', 'Miễn phí'),
                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 12),
                          child: Divider(),
                        ),
                        _PriceRow(
                          'Tổng cộng',
                          formatMoney(_cart?.total ?? 0),
                          emphasized: true,
                        ),
                      ],
                    ),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    Text(
                      _error!,
                      style: const TextStyle(color: AppColors.danger),
                    ),
                  ],
                ],
              ),
            ),
      bottomNavigationBar: _loading
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
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Tổng thanh toán',
                            style: TextStyle(
                              color: AppColors.muted,
                              fontSize: 11,
                            ),
                          ),
                          Text(
                            formatMoney(_cart?.total ?? 0),
                            style: const TextStyle(
                              color: AppColors.danger,
                              fontSize: 17,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ],
                      ),
                    ),
                    FilledButton.icon(
                      onPressed: _submitting ? null : _submit,
                      icon: _submitting
                          ? const SizedBox.square(
                              dimension: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.check_circle_outline),
                      label: Text(_submitting ? 'Đang xử lý...' : 'Đặt hàng'),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  String? _required(String? value) =>
      value?.trim().isNotEmpty == true ? null : 'Bắt buộc';
}

class _CheckoutSection extends StatelessWidget {
  const _CheckoutSection({
    required this.icon,
    required this.title,
    required this.child,
  });

  final IconData icon;
  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: AppColors.primary, size: 21),
                const SizedBox(width: 8),
                Text(title, style: Theme.of(context).textTheme.titleMedium),
              ],
            ),
            const SizedBox(height: 14),
            child,
          ],
        ),
      ),
    );
  }
}

class _PriceRow extends StatelessWidget {
  const _PriceRow(this.label, this.value, {this.emphasized = false});

  final String label;
  final String value;
  final bool emphasized;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(
            label,
            style: TextStyle(
              color: emphasized ? AppColors.text : AppColors.muted,
              fontWeight: emphasized ? FontWeight.w800 : FontWeight.w500,
            ),
          ),
        ),
        Text(
          value,
          style: TextStyle(
            color: emphasized ? AppColors.danger : AppColors.text,
            fontWeight: emphasized ? FontWeight.w900 : FontWeight.w700,
          ),
        ),
      ],
    );
  }
}
