# ReadyRide — Customer App (Flutter)
# Phase 1: Project Setup + Core Foundation

---

## Context
ReadyRide-এর Laravel API ready (base URL: https://api.readyride.com/api/v1)।
এই Flutter app Customer-দের জন্য — Ride book ও Parcel পাঠাবে।

Architecture: **MVVM + Provider**
API: **Dio**
Routing: **go_router**

---

## Folder Structure (অবশ্যই follow করবে)

```
lib/
├── main.dart
├── app.dart
│
├── core/
│   ├── constants/
│   │   ├── app_constants.dart      # base url, keys
│   │   ├── api_endpoints.dart      # all endpoint strings
│   │   └── app_colors.dart
│   ├── theme/
│   │   ├── app_theme.dart
│   │   └── app_text_styles.dart
│   ├── network/
│   │   ├── dio_client.dart         # Dio setup + interceptors
│   │   ├── api_response.dart       # standard response wrapper
│   │   └── api_exception.dart
│   ├── storage/
│   │   └── secure_storage.dart     # token storage
│   ├── routing/
│   │   ├── app_router.dart         # go_router config
│   │   └── route_names.dart
│   ├── utils/
│   │   ├── validators.dart
│   │   ├── helpers.dart
│   │   └── snackbar_helper.dart
│   └── widgets/                    # shared widgets
│       ├── custom_button.dart
│       ├── custom_textfield.dart
│       ├── loading_widget.dart
│       └── empty_state.dart
│
└── features/
    └── <feature_name>/
        ├── provider/
        │   └── <feature_name>_provider.dart
        ├── repository/
        │   ├── <feature_name>_repository.dart       # abstract
        │   └── <feature_name>_repository_impl.dart   # implementation
        ├── model/
        │   └── <feature_name>_model.dart
        └── views/
            ├── screen/
            │   └── <feature_name>_screen.dart
            └── widgets/
```

---

## এই Phase-এ যা করবে

1. Flutter project তৈরি + dependencies
2. Core layer (network, storage, theme, routing, utils)
3. Shared widgets
4. App entry point setup

---

## ১. Dependencies (pubspec.yaml)

```yaml
name: readyride_customer
description: ReadyRide Customer App

environment:
  sdk: '>=3.0.0 <4.0.0'

dependencies:
  flutter:
    sdk: flutter

  # State Management
  provider: ^6.1.2

  # Network
  dio: ^5.4.0

  # Routing
  go_router: ^13.0.0

  # Storage
  flutter_secure_storage: ^9.0.0
  shared_preferences: ^2.2.2

  # Maps & Location
  google_maps_flutter: ^2.5.3
  geolocator: ^10.1.0
  geocoding: ^2.1.1

  # Real-time
  pusher_channels_flutter: ^2.2.1

  # Push Notification
  firebase_core: ^2.24.2
  firebase_messaging: ^14.7.10
  flutter_local_notifications: ^16.3.0

  # Payment
  flutter_stripe: ^10.1.0

  # UI
  cached_network_image: ^3.3.1
  shimmer: ^3.0.0
  lottie: ^3.0.0
  flutter_svg: ^2.0.9
  smooth_page_indicator: ^1.1.0
  pinput: ^3.0.1                    # OTP input

  # Utils
  intl: ^0.19.0
  url_launcher: ^6.2.4
  image_picker: ^1.0.7
  permission_handler: ^11.2.0
  share_plus: ^7.2.1
  connectivity_plus: ^5.0.2

dev_dependencies:
  flutter_lints: ^3.0.1
```

---

## ২. Core — Constants

### app_constants.dart
```dart
class AppConstants {
  static const String appName = 'ReadyRide';
  static const String baseUrl = 'https://api.readyride.com/api/v1';
  // Development: 'http://10.0.2.2:8000/api/v1' (Android emulator)

  // Storage keys
  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String fcmTokenKey = 'fcm_token';
  static const String onboardingKey = 'onboarding_completed';

  // Config (will be fetched from /config endpoint)
  static const int otpLength = 6;
  static const int otpResendSeconds = 30;
  static const int locationUpdateInterval = 5; // seconds
}
```

### api_endpoints.dart
```dart
class ApiEndpoints {
  // Config
  static const String config = '/config';
  static const String faqs = '/faqs';
  static const String cancellationReasons = '/cancellation-reasons';

  // Auth
  static const String sendOtp = '/auth/send-otp';
  static const String verifyOtp = '/auth/verify-otp';
  static const String completeProfile = '/auth/complete-profile';
  static const String logout = '/auth/logout';

  // Profile
  static const String profile = '/user/profile';
  static const String updateFcmToken = '/user/update-fcm-token';
  static const String deleteAccount = '/user/account';

  // Home
  static const String services = '/services';
  static const String vehicleCategories = '/ride/vehicle-categories';
  static const String nearbyDrivers = '/nearby-drivers';

  // Ride
  static const String rideFareEstimate = '/ride/fare-estimate';
  static const String rideBook = '/ride/book';
  static String rideStatus(int id) => '/ride/$id/status';
  static String rideCancel(int id) => '/ride/$id/cancel';
  static String rideTip(int id) => '/ride/$id/tip';
  static String rideRate(int id) => '/ride/$id/rate';
  static String rideShareLink(int id) => '/ride/$id/generate-share-link';

  // Parcel
  static const String parcelEstimate = '/parcel/estimate';
  static const String parcelBook = '/parcel/book';
  static String parcelStatus(int id) => '/parcel/$id/status';
  static String parcelCancel(int id) => '/parcel/$id/cancel';
  static String parcelRate(int id) => '/parcel/$id/rate';
  static String parcelPayAfter(int id) => '/parcel/$id/pay-after-delivery';

  // Coupon
  static const String couponValidate = '/coupon/validate';
  static const String coupons = '/coupons';

  // Wallet
  static const String wallet = '/user/wallet';
  static const String walletTransactions = '/user/wallet/transactions';
  static const String topupInitiate = '/user/wallet/topup/initiate';
  static const String topupConfirm = '/user/wallet/topup/confirm';

  // Orders
  static const String orders = '/user/orders';
  static String orderDetail(int id) => '/user/orders/$id';
  static String invoice(int id) => '/orders/$id/invoice';
  static const String scheduledOrders = '/user/scheduled-orders';

  // Favourite Locations
  static const String favouriteLocations = '/user/favourite-locations';

  // Notifications
  static const String notifications = '/user/notifications';
  static const String notificationsMarkRead = '/user/notifications/mark-read';

  // SOS & Emergency
  static const String sos = '/user/sos';
  static const String emergencyContact = '/user/emergency-contact';

  // Referral
  static const String referral = '/user/referral';

  // Complaints
  static const String complaints = '/user/complaints';

  // Geocode
  static const String geocodeSearch = '/geocode/search';
  static const String geocodeReverse = '/geocode/reverse';
}
```

### app_colors.dart
```dart
import 'package:flutter/material.dart';

class AppColors {
  static const Color primary = Color(0xFF1A56DB);
  static const Color primaryDark = Color(0xFF1E429F);
  static const Color secondary = Color(0xFF059669);
  static const Color accent = Color(0xFFD97706);

  static const Color success = Color(0xFF057A55);
  static const Color warning = Color(0xFFC27803);
  static const Color danger = Color(0xFFC81E1E);

  static const Color background = Color(0xFFF9FAFB);
  static const Color surface = Color(0xFFFFFFFF);
  static const Color textPrimary = Color(0xFF111928);
  static const Color textSecondary = Color(0xFF6B7280);
  static const Color border = Color(0xFFE5E7EB);
}
```

---

## ৩. Core — Network

### dio_client.dart
```dart
import 'package:dio/dio.dart';
import '../constants/app_constants.dart';
import '../storage/secure_storage.dart';

class DioClient {
  late final Dio _dio;
  final SecureStorage _storage;

  DioClient(this._storage) {
    _dio = Dio(BaseOptions(
      baseUrl: AppConstants.baseUrl,
      connectTimeout: const Duration(seconds: 30),
      receiveTimeout: const Duration(seconds: 30),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ));

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        // Auth token attach
        final token = await _storage.getToken();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (error, handler) async {
        // 401 → logout / redirect to login
        if (error.response?.statusCode == 401) {
          await _storage.clearAll();
          // TODO: redirect to login (use navigator key)
        }
        handler.next(error);
      },
    ));

    // Logging (development only)
    _dio.interceptors.add(LogInterceptor(
      requestBody: true,
      responseBody: true,
    ));
  }

  Dio get dio => _dio;
}
```

### api_response.dart
```dart
class ApiResponse<T> {
  final bool success;
  final String message;
  final T? data;
  final Map<String, dynamic>? errors;
  final PaginationMeta? meta;

  ApiResponse({
    required this.success,
    required this.message,
    this.data,
    this.errors,
    this.meta,
  });

  factory ApiResponse.fromJson(
    Map<String, dynamic> json,
    T Function(dynamic)? fromData,
  ) {
    return ApiResponse(
      success: json['success'] ?? false,
      message: json['message'] ?? '',
      data: json['data'] != null && fromData != null
          ? fromData(json['data'])
          : json['data'],
      errors: json['errors'],
      meta: json['meta'] != null
          ? PaginationMeta.fromJson(json['meta'])
          : null,
    );
  }
}

class PaginationMeta {
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  PaginationMeta({
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  factory PaginationMeta.fromJson(Map<String, dynamic> json) {
    return PaginationMeta(
      currentPage: json['current_page'] ?? 1,
      lastPage: json['last_page'] ?? 1,
      perPage: json['per_page'] ?? 20,
      total: json['total'] ?? 0,
    );
  }

  bool get hasMore => currentPage < lastPage;
}
```

### api_exception.dart
```dart
import 'package:dio/dio.dart';

class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  ApiException({
    required this.message,
    this.statusCode,
    this.errors,
  });

  factory ApiException.fromDioError(DioException error) {
    String message = 'Something went wrong';
    int? statusCode = error.response?.statusCode;
    Map<String, dynamic>? errors;

    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.receiveTimeout) {
      message = 'Connection timeout. Please check your internet.';
    } else if (error.type == DioExceptionType.connectionError) {
      message = 'No internet connection.';
    } else if (error.response != null) {
      final data = error.response!.data;
      if (data is Map) {
        message = data['message'] ?? message;
        errors = data['errors'];
      }
    }

    return ApiException(
      message: message,
      statusCode: statusCode,
      errors: errors,
    );
  }

  // First validation error message
  String? get firstError {
    if (errors != null && errors!.isNotEmpty) {
      final first = errors!.values.first;
      if (first is List && first.isNotEmpty) return first.first;
    }
    return null;
  }
}
```

---

## ৪. Core — Storage

### secure_storage.dart
```dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../constants/app_constants.dart';

class SecureStorage {
  final _storage = const FlutterSecureStorage();

  Future<void> saveToken(String token) async {
    await _storage.write(key: AppConstants.tokenKey, value: token);
  }

  Future<String?> getToken() async {
    return await _storage.read(key: AppConstants.tokenKey);
  }

  Future<void> saveUser(String userJson) async {
    await _storage.write(key: AppConstants.userKey, value: userJson);
  }

  Future<String?> getUser() async {
    return await _storage.read(key: AppConstants.userKey);
  }

  Future<bool> hasToken() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  Future<void> clearAll() async {
    await _storage.deleteAll();
  }
}
```

---

## ৫. Core — Routing

### route_names.dart
```dart
class RouteNames {
  static const String splash = '/';
  static const String onboarding = '/onboarding';
  static const String phoneEntry = '/phone-entry';
  static const String otpVerify = '/otp-verify';
  static const String completeProfile = '/complete-profile';
  static const String home = '/home';
  static const String setDestination = '/set-destination';
  static const String vehicleSelect = '/vehicle-select';
  static const String bookingConfirm = '/booking-confirm';
  static const String rideTracking = '/ride-tracking';
  static const String parcelBooking = '/parcel-booking';
  static const String parcelTracking = '/parcel-tracking';
  static const String wallet = '/wallet';
  static const String history = '/history';
  static const String orderDetail = '/order-detail';
  static const String notifications = '/notifications';
  static const String profile = '/profile';
  static const String favourites = '/favourites';
  static const String referral = '/referral';
  static const String settings = '/settings';
}
```

### app_router.dart
```dart
import 'package:go_router/go_router.dart';
// import screens...

class AppRouter {
  static GoRouter router(/* auth provider */) {
    return GoRouter(
      initialLocation: RouteNames.splash,
      // redirect logic: token আছে কিনা → home, না হলে → phone-entry
      routes: [
        GoRoute(
          path: RouteNames.splash,
          builder: (context, state) => const SplashScreen(),
        ),
        // ... সব routes এখানে যোগ হবে phase অনুযায়ী
      ],
    );
  }
}
```

---

## ৬. Core — Theme

### app_theme.dart
```dart
import 'package:flutter/material.dart';
import '../constants/app_colors.dart';

class AppTheme {
  static ThemeData light = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: AppColors.primary,
      primary: AppColors.primary,
    ),
    scaffoldBackgroundColor: AppColors.background,
    appBarTheme: const AppBarTheme(
      backgroundColor: AppColors.surface,
      foregroundColor: AppColors.textPrimary,
      elevation: 0,
      centerTitle: true,
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        padding: const EdgeInsets.symmetric(vertical: 16),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: AppColors.surface,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppColors.border),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppColors.border),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppColors.primary, width: 2),
      ),
    ),
  );
}
```

---

## ৭. Shared Widgets

### custom_button.dart
```dart
// Props: text, onPressed, isLoading, isOutlined, icon, color
// Loading হলে CircularProgressIndicator দেখাবে
// Full-width by default
```

### custom_textfield.dart
```dart
// Props: controller, label, hint, validator, keyboardType,
//        prefixIcon, suffixIcon, obscureText, maxLength
```

### loading_widget.dart
```dart
// Full screen loading + shimmer variants
```

### empty_state.dart
```dart
// Props: icon, title, message, actionText, onAction
// কোনো data না থাকলে দেখাবে
```

---

## ৮. Core — Utils

### validators.dart
```dart
class Validators {
  static String? phone(String? value) {
    if (value == null || value.isEmpty) return 'Phone number is required';
    final regex = RegExp(r'^01[3-9]\d{8}$');
    if (!regex.hasMatch(value)) return 'Enter a valid phone number';
    return null;
  }

  static String? required(String? value, [String field = 'This field']) {
    if (value == null || value.trim().isEmpty) return '$field is required';
    return null;
  }

  static String? email(String? value) {
    if (value == null || value.isEmpty) return null; // optional
    final regex = RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
    if (!regex.hasMatch(value)) return 'Enter a valid email';
    return null;
  }
}
```

### snackbar_helper.dart
```dart
// showSuccess(context, message)
// showError(context, message)
// showInfo(context, message)
// Top-positioned, auto-dismiss, colored
```

---

## ৯. App Entry

### main.dart
```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
// imports...

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Firebase.initializeApp()
  // Stripe setup
  runApp(const MyApp());
}
```

### app.dart
```dart
class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        // Core providers
        Provider<SecureStorage>(create: (_) => SecureStorage()),
        Provider<DioClient>(
          create: (ctx) => DioClient(ctx.read<SecureStorage>()),
        ),
        // Feature providers (phase অনুযায়ী যোগ হবে)
      ],
      child: MaterialApp.router(
        title: 'ReadyRide',
        theme: AppTheme.light,
        debugShowCheckedModeBanner: false,
        routerConfig: AppRouter.router(),
      ),
    );
  }
}
```

---

## গুরুত্বপূর্ণ নিয়ম

1. প্রতিটা feature **MVVM + Provider** structure মেনে বানাবে
2. Repository **abstract + impl** আলাদা রাখবে (testability)
3. সব API call **Repository**-তে হবে, Provider শুধু state রাখবে
4. Network error **ApiException** দিয়ে handle করবে
5. Token **SecureStorage**-এ রাখবে (SharedPreferences না)
6. Hard-coded string এড়িয়ে constants ব্যবহার করবে
7. সব screen **responsive** — MediaQuery বা flutter_screenutil

---

## এই Phase-এ Deliverable

- [ ] Flutter project + pubspec dependencies
- [ ] Complete core/ folder (constants, network, storage, routing, theme, utils)
- [ ] Shared widgets (button, textfield, loading, empty)
- [ ] main.dart + app.dart with MultiProvider
- [ ] Empty go_router setup
- [ ] একটা placeholder SplashScreen

---

## শুরু করো এই order-এ

1. `flutter create readyride_customer`
2. pubspec.yaml dependencies যোগ + `flutter pub get`
3. core/constants
4. core/network (dio_client, api_response, api_exception)
5. core/storage
6. core/theme
7. core/utils
8. core/widgets (shared)
9. core/routing (empty setup)
10. main.dart + app.dart
11. Placeholder SplashScreen
12. `flutter run` — app চলে কিনা check করো
