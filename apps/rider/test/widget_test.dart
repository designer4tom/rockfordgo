// Basic smoke test for the ReadyRide customer app.

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:readyride_customer/app.dart';
import 'package:readyride_customer/core/constants/app_constants.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  const secureStorageChannel =
      MethodChannel('plugins.it_nomads.com/flutter_secure_storage');

  setUp(() async {
    TestWidgetsFlutterBinding.ensureInitialized();

    // No persisted onboarding flag — set before any getInstance call.
    SharedPreferences.setMockInitialValues({});
    await EasyLocalization.ensureInitialized();

    // Stub flutter_secure_storage so reads return null (no token).
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(secureStorageChannel, (call) async {
      if (call.method == 'readAll') return <String, String>{};
      return null;
    });
  });

  tearDown(() {
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(secureStorageChannel, null);
  });

  testWidgets('App boots and shows the splash screen', (tester) async {
    final prefs = await SharedPreferences.getInstance();

    await tester.pumpWidget(
      EasyLocalization(
        supportedLocales: AppConstants.supportedLocales,
        path: AppConstants.translationsPath,
        fallbackLocale: AppConstants.fallbackLocale,
        startLocale: const Locale('en'),
        child: MyApp(prefs: prefs),
      ),
    );
    // Let easy_localization load translations + first frame render.
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 100));

    // The splash screen shows the brand logo (locale-independent).
    expect(find.byIcon(Icons.local_taxi_rounded), findsOneWidget);

    // Let the splash delay elapse so no timers remain pending.
    await tester.pump(const Duration(milliseconds: 3100));
    await tester.pumpAndSettle();
  });
}
