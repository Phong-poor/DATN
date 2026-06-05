import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';
import '../features/auth/presentation/auth_gate.dart';
import 'app_dependencies.dart';

class DatnApp extends StatelessWidget {
  DatnApp({super.key, AppDependencies? dependencies})
    : dependencies = dependencies ?? AppDependencies.create();

  final AppDependencies dependencies;

  @override
  Widget build(BuildContext context) {
    return AppScope(
      dependencies: dependencies,
      child: MaterialApp(
        debugShowCheckedModeBanner: false,
        title: 'Predator Group',
        theme: AppTheme.light,
        home: const AuthGate(),
      ),
    );
  }
}
