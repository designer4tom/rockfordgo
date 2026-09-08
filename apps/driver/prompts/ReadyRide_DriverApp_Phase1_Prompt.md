# ReadyRide — Driver App (Flutter)
# Phase 1: Project Setup + Core Foundation

---

## Context
ReadyRide Laravel API ready। এই Flutter app **Driver**-দের জন্য — Ride ও Parcel delivery করবে।
Customer App-এর মতোই architecture, কিন্তু driver-specific endpoints।

Architecture: **MVVM + Provider** | API: **Dio** | Routing: **go_router**

---

## Folder Structure (অবশ্যই follow করবে)

```
lib/
├── main.dart
├── app.dart
│
├── core/
│   ├── constants/
│   │   ├── app_constants.dart
│   │   ├── api_endpoints.dart       # driver endpoints
│   │   └── app_colors.dart
│   ├── theme/
│   ├── network/
│   │   ├── dio_client.dart
│   │   ├── pusher_service.dart
│   │   ├── api_response.dart
│   │   └── api_exception.dart
│   ├── storage/
│   │   └── secure_storage.dart
│   ├── services/
│   │   ├── fcm_service.dart
│   │   └── location_service.dart    # background location
│   ├── routing/
│   ├── utils/
│   └── widgets/
│
└── features/
    └── <feature_name>/
        ├── provider/
        ├── repository/
        ├── model/
        └── views/{screen, widgets}/
```

---

## এই Phase-এ যা করবে

1. Flutter project + dependencies
2. Core layer (network, storage, theme, routing)
3. Background location service setup
4. Shared widgets
5. App entry point

---

## ১. Dependencies (pubspec.yaml)

```yaml
name: readyride_driver
description: ReadyRide Driver App

environment:
  sdk: '>=3.0.0 <4.0.0'

dependencies:
  flutter:
    sdk: flutter

  # State
  provider: ^6.1.2

  # Network
  dio: ^5.4.0

  # Routing
  go_router: ^13.0.0

  # Storage
  flutter_secure_storage: ^9.0.0
  shared_preferences: ^2.2.2

  # Maps & Location (Driver needs background)
  google_maps_flutter: ^2.5.3
  geolocator: ^10.1.0
  flutter_background_geolocation: ^4.15.0  # বা geolocator background
  google_maps_flutter_android: ^2.7.0

  # Navigation (turn-by-turn)
  url_launcher: ^6.2.4   # external Google Maps navigation

  # Real-time
  pusher_channels_flutter: ^2.2.1

  # Push Notification
  firebase_core: ^2.24.2
  firebase_messaging: ^14.7.10
  flutter_local_notifications: ^16.3.0

  # UI
  cached_network_image: ^3.3.1
  shimmer: ^3.0.0
  lottie: ^3.0.0
  flutter_svg: ^2.0.9
  pinput: ^3.0.1                  # OTP verify (driver enters)
  fl_chart: ^0.66.0              # earnings chart
  signature: ^5.4.0              # proof of delivery signature

  # Utils
  intl: ^0.19.0
  image_picker: ^1.0.7           # document + proof photo
  permission_handler: ^11.2.0
  connectivity_plus: ^5.0.2
  audioplayers: ^5.2.1           # order request sound

dev_dependencies:
  flutter_lints: ^3.0.1
```

---

## ২. Core — Constants

### app_constants.dart
```dart
class AppConstants {
  static const String appName = 'ReadyRide Driver';
  static const String baseUrl = 'https://api.readyride.com/api/v1';

  static const String tokenKey = 'driver_auth_token';
  static const String driverKey = 'driver_data';
  static const String onboardingKey = 'onboarding_completed';

  static const int otpLength = 6;
  static const int otpResendSeconds = 30;
  static const int locationUpdateInterval = 5;     // seconds
  static const int orderRequestTimeout = 30;       // seconds popup
}
```

### api_endpoints.dart
```dart
class ApiEndpoints {
  // Config
  static const String config = '/config';
  static const String faqs = '/faqs';

  // Driver Auth
  static const String sendOtp = '/driver/auth/send-otp';
  static const String verifyOtp = '/driver/auth/verify-otp';
  static const String register = '/driver/auth/register';
  static const String authStatus = '/driver/auth/status';
  static const String logout = '/driver/auth/logout';

  // Profile
  static const String profile = '/driver/profile';
  static const String updateFcmToken = '/driver/update-fcm-token';
  static const String updateDocument = '/driver/documents/update';
  static const String deleteAccount = '/driver/account';
  static const String emergencyContact = '/driver/emergency-contact';

  // Online & Location
  static const String toggleOnline = '/driver/toggle-online';
  static const String updateLocation = '/driver/update-location';
  static const String activeOrder = '/driver/active-order';

  // Ride
  static const String rideRespond = '/driver/ride/respond';
  static const String rideUpdateStatus = '/driver/ride/update-status';
  static const String rideCollectProof = '/driver/ride/collect-proof';
  static String rideRate(int id) => '/driver/ride/$id/rate';

  // Parcel
  static const String parcelUpdateStatus = '/driver/parcel/update-status';
  static const String parcelCollectCod = '/driver/parcel/collect-cod';
  static const String parcelComplete = '/driver/parcel/complete';

  // Wallet
  static const String wallet = '/driver/wallet';
  static const String walletTransactions = '/driver/wallet/transactions';
  static const String withdrawalRequest = '/driver/wallet/withdrawal/request';
  static const String withdrawalHistory = '/driver/wallet/withdrawal/history';

  // Earnings
  static const String earnings = '/driver/earnings';
  static const String earningsSummary = '/driver/earnings/summary';
  static const String earningsChart = '/driver/earnings/chart';

  // Performance & Shifts
  static const String performance = '/driver/performance';
  static const String shifts = '/driver/shifts';

  // Orders
  static const String orders = '/driver/orders';
  static String orderDetail(int id) => '/driver/orders/$id';

  // Notifications
  static const String notifications = '/driver/notifications';
  static const String notificationsMarkRead = '/driver/notifications/mark-read';

  // SOS
  static const String sos = '/driver/sos';

  // Complaints
  static const String complaints = '/driver/complaints';

  // Vehicle Categories (for registration)
  static const String vehicleCategories = '/ride/vehicle-categories';
}
```

### app_colors.dart
```dart
// Driver app theme — accent color আলাদা (amber/orange feel)
class AppColors {
  static const Color primary = Color(0xFFD97706);     // amber
  static const Color primaryDark = Color(0xFF92400E);
  static const Color secondary = Color(0xFF1A56DB);
  static const Color online = Color(0xFF057A55);      // green
  static const Color offline = Color(0xFF6B7280);

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
```
(Customer App-এর মতোই — token interceptor, 401 handle, logging)
শুধু token key: driver_auth_token
```

### pusher_service.dart
```dart
// Driver primarily listens: private-driver.{driverId}
// Events: NewOrderRequest, OrderCancelled

class PusherService {
  Future<void> init();
  Future<void> subscribeToDriverChannel(int driverId, {
    required Function(dynamic) onNewOrder,
    required Function(dynamic) onOrderCancelled,
  });
  Future<void> unsubscribe(String channel);
  Future<void> disconnect();
}
```

### api_response.dart, api_exception.dart
```
(Customer App-এর সাথে identical)
```

---

## ৪. Core — Services

### location_service.dart (CRITICAL for driver)
```dart
class LocationService {
  // Foreground + Background location tracking

  Future<bool> requestPermissions();        // including background
  Future<Position> getCurrentLocation();
  Stream<Position> getLocationStream();      // continuous

  // Background tracking (driver online থাকলে)
  Future<void> startBackgroundTracking({
    required Function(Position) onLocation,
  });
  Future<void> stopBackgroundTracking();

  // Battery optimization
  // - Online কিন্তু no trip: low frequency (10s)
  // - Active trip: high frequency (3-5s)
  void setUpdateInterval(int seconds);
}
```

### fcm_service.dart
```dart
class FcmService {
  Future<void> init();
  Future<String?> getToken();
  void onMessage(Function(RemoteMessage) handler);
  void onMessageOpenedApp(Function(RemoteMessage) handler);
  static Future<void> backgroundHandler(RemoteMessage message);

  // Order request notification — high priority
  // App background → notification tap → open order popup
}
```

---

## ৫. Core — Storage, Theme, Routing, Utils, Widgets

```
secure_storage.dart  — token, driver data (Customer-এর মতো)
theme/               — AppTheme (amber primary)
routing/             — route_names + app_router (empty setup)
utils/               — validators, helpers, snackbar
widgets/             — custom_button, custom_textfield, loading, empty_state
```

### route_names.dart
```dart
class RouteNames {
  static const String splash = '/';
  static const String onboarding = '/onboarding';
  static const String phoneEntry = '/phone-entry';
  static const String otpVerify = '/otp-verify';
  static const String registration = '/registration';
  static const String pendingApproval = '/pending-approval';
  static const String home = '/home';
  static const String orderRequest = '/order-request';
  static const String rideOrder = '/ride-order';
  static const String parcelOrder = '/parcel-order';
  static const String earnings = '/earnings';
  static const String wallet = '/wallet';
  static const String withdrawal = '/withdrawal';
  static const String documents = '/documents';
  static const String history = '/history';
  static const String performance = '/performance';
  static const String notifications = '/notifications';
  static const String profile = '/profile';
  static const String settings = '/settings';
}
```

---

## ৬. App Entry (main.dart + app.dart)

```dart
// main.dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Firebase.initializeApp()
  // FCM background handler register
  // Stripe (driver might not need — skip)
  runApp(const DriverApp());
}

// app.dart — MultiProvider (core providers)
```

---

## গুরুত্বপূর্ণ নিয়ম

1. **Background location** — driver-এর জন্য critical, permission ঠিকভাবে handle
2. Token key **driver_auth_token** (Customer থেকে আলাদা)
3. Theme **amber/orange** — driver app আলাদা feel
4. FCM **high priority** channel — order request যেন miss না হয়
5. Order request **sound** — audioplayers দিয়ে
6. Battery optimization — trip না থাকলে location frequency কমাবে

---

## Deliverable

- [ ] Flutter project + dependencies
- [ ] Core (constants, network, storage, theme, routing, utils, widgets)
- [ ] Location service (foreground + background)
- [ ] FCM service
- [ ] Pusher service
- [ ] main.dart + app.dart
- [ ] Placeholder splash

---

## শুরু করো এই order-এ

1. `flutter create readyride_driver`
2. Dependencies + pub get
3. core/constants
4. core/network (dio, pusher, response, exception)
5. core/services (location, fcm)
6. core/storage, theme, utils, widgets
7. core/routing (empty)
8. main.dart + app.dart
9. Placeholder splash
10. `flutter run`
