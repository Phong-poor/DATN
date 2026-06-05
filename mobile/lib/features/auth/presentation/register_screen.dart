import 'package:flutter/material.dart';

import '../../../app/app_dependencies.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/widgets/commerce_widgets.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  bool _obscure = true;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final success = await AppScope.of(context).authController.register(
      name: _name.text,
      email: _email.text,
      phone: _phone.text,
      password: _password.text,
    );
    if (success && mounted) Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) {
    final auth = AppScope.of(context).authController;
    return Scaffold(
      appBar: AppBar(title: const Text('Tạo tài khoản')),
      body: ListenableBuilder(
        listenable: auth,
        builder: (context, _) => Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 14, 20, 30),
            children: [
              const Align(alignment: Alignment.centerLeft, child: BrandMark()),
              const SizedBox(height: 20),
              Text(
                'Tham gia Predator Group',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: 5),
              const Text(
                'Tạo tài khoản để đặt hàng và nhận ưu đãi.',
                style: TextStyle(color: AppColors.muted),
              ),
              const SizedBox(height: 20),
              _field(_name, 'Họ tên', Icons.person_outline),
              _field(
                _email,
                'Email',
                Icons.email_outlined,
                type: TextInputType.emailAddress,
              ),
              _field(
                _phone,
                'Số điện thoại',
                Icons.phone_outlined,
                type: TextInputType.phone,
              ),
              TextFormField(
                controller: _password,
                obscureText: _obscure,
                decoration: InputDecoration(
                  labelText: 'Mật khẩu',
                  prefixIcon: const Icon(Icons.lock_outline),
                  suffixIcon: IconButton(
                    tooltip: _obscure ? 'Hiện mật khẩu' : 'Ẩn mật khẩu',
                    onPressed: () => setState(() => _obscure = !_obscure),
                    icon: Icon(
                      _obscure ? Icons.visibility : Icons.visibility_off,
                    ),
                  ),
                ),
                validator: _validatePassword,
              ),
              const SizedBox(height: 7),
              const Text(
                'Tối thiểu 8 ký tự, có chữ hoa, chữ thường, số và ký tự đặc biệt.',
                style: TextStyle(color: AppColors.muted, fontSize: 11),
              ),
              const SizedBox(height: 16),
              if (auth.error != null) ...[
                Text(
                  auth.error!,
                  style: const TextStyle(color: AppColors.danger),
                ),
                const SizedBox(height: 10),
              ],
              FilledButton.icon(
                onPressed: auth.busy ? null : _submit,
                icon: auth.busy
                    ? const SizedBox.square(
                        dimension: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.person_add_alt_1),
                label: const Text('Đăng ký'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _field(
    TextEditingController controller,
    String label,
    IconData icon, {
    TextInputType? type,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: TextFormField(
      controller: controller,
      keyboardType: type,
      decoration: InputDecoration(labelText: label, prefixIcon: Icon(icon)),
      validator: (value) =>
          value?.trim().isNotEmpty == true ? null : 'Bắt buộc',
    ),
  );

  String? _validatePassword(String? value) {
    final password = value ?? '';
    if (password.length < 8) return 'Mật khẩu tối thiểu 8 ký tự';
    final valid =
        RegExp(r'[A-Z]').hasMatch(password) &&
        RegExp(r'[a-z]').hasMatch(password) &&
        RegExp(r'[0-9]').hasMatch(password) &&
        RegExp(r'[^A-Za-z0-9]').hasMatch(password);
    return valid ? null : 'Mật khẩu chưa đúng định dạng';
  }
}
