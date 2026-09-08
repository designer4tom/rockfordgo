import 'package:flutter/widgets.dart';

class AppConstants {
  static const String appName = 'ReadyRide';
  static const String appVersion = '2.4.0';
  static const String baseUrl = 'https://readyride.razinsoft.com/api/v1';
  // static const String baseUrl = 'http://192.168.10.54:8000/api/v1';

  // Storage keys
  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String fcmTokenKey = 'fcm_token';
  static const String onboardingKey = 'onboarding_completed';
  static const String languageKey = 'app_language';
  static const String themeKey = 'app_theme';

  // Localization
  static const String defaultLanguage = 'en'; // change here to flip default
  static const List<Locale> supportedLocales = [Locale('en'), Locale('ar')];
  static const Locale fallbackLocale = Locale('en');
  static const String translationsPath = 'assets/translations';

  // Config (will be fetched from /config endpoint)
  static const int otpLength = 6;
  static const int otpResendSeconds = 30;
  static const int locationUpdateInterval = 5; // seconds

  // Pusher (TODO: fetch from /config). Placeholders for now.
  static const String pusherKey = '1bf8618055e6bdaf8be4';
  static const String pusherCluster = 'ap2';

  // Google Maps key for Directions API (road-following route polyline).
  // Use the SAME key as the native config, with "Directions API" enabled.
  // static const String googleMapsApiKey = 'AIzaSyB-iGXLTfsGrwWMwbfNFVKctmPBbZIOJvI';
  static const String googleMapsApiKey ='AIzaSyDpiIiLpkPGZn8nL0aRkT1r5XVOV5X7aKU';
}
