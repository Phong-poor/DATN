import 'package:flutter/material.dart';

import '../../../app/app_dependencies.dart';
import '../../home/presentation/main_shell.dart';
import 'auth_controller.dart';

class AuthGate extends StatefulWidget {
  const AuthGate({super.key});

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  bool _started = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_started) {
      _started = true;
      AppScope.of(context).authController.checkAuth();
    }
  }

  @override
  Widget build(BuildContext context) {
    final controller = AppScope.of(context).authController;
    return ListenableBuilder(
      listenable: controller,
      builder: (context, _) => MainShell(
        authenticated: controller.status == AuthStatus.authenticated,
      ),
    );
  }
}
