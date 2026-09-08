import 'dart:ui';

class AppConstants {
  static const String appName = 'ReadyRide Driver';
  static const String appVersion = '1.0.0';

  // ---- Localization (English default + Arabic RTL) ----
  static const String defaultLanguage = 'en';
  static const List<Locale> supportedLocales = [Locale('en'), Locale('ar')];
  static const Locale fallbackLocale = Locale('en');
  static const String translationsPath = 'assets/translations';
  static const String languageKey = 'app_language';

  // ---- Theme ----
  static const String themeKey = 'app_theme';

  // Live API base URL
  //static const String baseUrl = 'https://uat.readyride.app/api/v1';
  static const String baseUrl = 'https://readyride.razinsoft.com/api/v1';
  //static const String baseUrl = 'http://192.168.10.54:8000/api/v1';

  // ---- Realtime (Pusher) ----
  // Used directly by Dart (PusherService). If left empty, the app falls back
  // to the key/cluster returned by the backend /config endpoint.
  static const String pusherKey = '1bf8618055e6bdaf8be4';
  static const String pusherCluster = 'ap2';

  // ---- Google Maps ----
  // NOTE: the native map SDK reads the key from AndroidManifest.xml /
  // iOS AppDelegate, NOT from here. Keep this in sync with those files — it is
  // the single documented value, but the native entry is what actually applies.
  static const String googleMapsKey = 'AIzaSyDpiIiLpkPGZn8nL0aRkT1r5XVOV5X7aKU';

  // Storage keys
  static const String tokenKey = 'driver_auth_token';
  static const String driverKey = 'driver_data';
  static const String onboardingKey = 'onboarding_completed';

  // OTP
  static const int otpLength = 6; // phone login OTP (auth)
  static const int rideOtpLength = 4; // ride/parcel pickup & delivery OTP
  static const int otpResendSeconds = 30;

  // Location / order timing
  static const int locationUpdateInterval = 5; // seconds (default)
  static const int locationIntervalIdle = 10; // online, no trip
  static const int locationIntervalActive = 4; // active trip
  static const int orderRequestTimeout = 30; // seconds popup
}
