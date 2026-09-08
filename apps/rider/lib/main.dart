import 'package:easy_localization/easy_localization.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'app.dart';
import 'core/constants/app_constants.dart';
import 'core/services/fcm_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await EasyLocalization.ensureInitialized();

  final prefs = await SharedPreferences.getInstance();

  // Payments use the gateway's hosted Checkout in a webview (Add Money flow),
  // so no native payment SDK init is needed here.

  // Firebase / FCM — native config comes from google-services.json (Android)
  // and GoogleService-Info.plist (iOS). Guarded so a missing/misconfigured
  // setup never blocks app startup.
  try {
    await Firebase.initializeApp();
    await FcmService().init();
  } catch (e) {
    debugPrint('Firebase init skipped: $e');
  }

  runApp(
    EasyLocalization(
      supportedLocales: AppConstants.supportedLocales,
      path: AppConstants.translationsPath,
      fallbackLocale: AppConstants.fallbackLocale,
      startLocale: const Locale(AppConstants.defaultLanguage),
      // saveLocale defaults to true — the user's language choice is persisted.
      child: MyApp(prefs: prefs),
    ),
  );
}
