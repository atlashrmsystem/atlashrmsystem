import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_app/main.dart';
import 'package:mobile_app/src/providers/auth_provider.dart';
import 'package:provider/provider.dart';

void main() {
  testWidgets('App boots and renders login screen shell', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(
      MultiProvider(
        providers: [ChangeNotifierProvider(create: (_) => AuthProvider())],
        child: const AtlasMobileApp(),
      ),
    );

    await tester.pump(const Duration(milliseconds: 200));

    expect(find.byType(AtlasMobileApp), findsOneWidget);
  });
}
