import 'package:easy_localization/easy_localization.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'app.dart';
import 'core/constants/app_constants.dart';
import 'core/services/fcm_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await EasyLocalization.ensureInitialized();

  final prefs = await SharedPreferences.getInstance();

  // Firebase + FCM. Guarded so the app still boots before google-services
  // config is added (real config wired in a later phase).
  try {
    await Firebase.initializeApp();
    FirebaseMessaging.onBackgroundMessage(FcmService.backgroundHandler);
    await FcmService.instance.init();
    // Capture the notification that cold-launched the app (terminated → tap)
    // here, before any screen builds, so it can never be missed due to timing.
    FcmService.initialMessage = await FirebaseMessaging.instance.getInitialMessage();
    debugPrint('FCMTAP initialMessage: ${FcmService.initialMessage?.data}');
  } catch (e) {
    debugPrint('Firebase init skipped: $e');
  }

  runApp(
    EasyLocalization(
      supportedLocales: AppConstants.supportedLocales,
      path: AppConstants.translationsPath,
      fallbackLocale: AppConstants.fallbackLocale,
      startLocale: const Locale(AppConstants.defaultLanguage),
      child: DriverApp(prefs: prefs),
    ),
  );
}
