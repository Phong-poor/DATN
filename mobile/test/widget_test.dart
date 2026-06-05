import 'package:datn_mobile/app/app.dart';
import 'package:datn_mobile/app/app_dependencies.dart';
import 'package:datn_mobile/core/storage/auth_storage.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('allows public shopping when no token is stored', (tester) async {
    final dependencies = AppDependencies.create(
      authStorage: MemoryAuthStorage(),
    );

    await tester.pumpWidget(DatnApp(dependencies: dependencies));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 100));

    expect(find.text('Trang chủ'), findsOneWidget);
    expect(find.text('Danh mục'), findsOneWidget);
  });
}
