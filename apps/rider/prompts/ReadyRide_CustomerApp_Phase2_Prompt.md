# ReadyRide — Customer App (Flutter)
# Phase 2: Authentication (OTP Login)

---

## Context
Phase 1 শেষ — Core foundation (network, storage, routing, theme) ready।
এই phase-এ Auth flow বানাবো: Splash → Onboarding → Phone → OTP → Profile → Home।

Architecture: **MVVM + Provider** | API: **Dio** | Routing: **go_router**

---

## 🎨 Design Image Instruction
> **যদি design image দেওয়া হয়:** সেই design **হুবহু** follow করবে — color, spacing, font, layout সব। আমার নিচের layout description শুধু reference, design image থাকলে সেটাই priority পাবে।
>
> **যদি design image না দেওয়া হয়:** নিচের layout description অনুযায়ী clean, modern UI বানাবে (theme থেকে color নেবে)।

---

## এই Phase-এ যে Features বানাবে

```
features/
├── splash/
├── onboarding/
└── auth/
```

---

## ১. Splash Feature

```
features/splash/
└── views/screen/splash_screen.dart
```

### Logic
```
App open হলে:
1. ১.৫ সেকেন্ড logo দেখাবে
2. Background-এ check করবে:
   - Onboarding completed? (SharedPreferences)
   - Token আছে? (SecureStorage)
3. Redirect:
   - Token আছে → /home
   - Token নেই + onboarding done → /phone-entry
   - Token নেই + onboarding বাকি → /onboarding
4. একই সাথে /config endpoint call করে app config cache করবে
```

### Layout (design image না থাকলে)
```
- Center-এ ReadyRide logo
- নিচে app name
- Background: primary color বা gradient
- Subtle loading indicator
```

---

## ২. Onboarding Feature

```
features/onboarding/
├── provider/onboarding_provider.dart
└── views/
    ├── screen/onboarding_screen.dart
    └── widgets/onboarding_page.dart
```

### Content (৩টা slide)
```
Slide 1: "যেকোনো জায়গায় যান" — Ride booking
Slide 2: "পার্সেল পাঠান সহজে" — Parcel delivery
Slide 3: "নিরাপদ ও দ্রুত" — Safety & speed
```

### Layout
```
- PageView (3 pages)
- প্রতি page: Illustration/Image + Title + Description
- smooth_page_indicator (dots)
- "Skip" button (top-right)
- "Next" button → শেষ page-এ "Get Started"
- "Get Started" → onboarding_completed = true → /phone-entry
```

---

## ৩. Auth Feature

```
features/auth/
├── provider/
│   └── auth_provider.dart
├── repository/
│   ├── auth_repository.dart
│   └── auth_repository_impl.dart
├── model/
│   ├── user_model.dart
│   └── auth_response_model.dart
└── views/
    ├── screen/
    │   ├── phone_entry_screen.dart
    │   ├── otp_verify_screen.dart
    │   └── complete_profile_screen.dart
    └── widgets/
        └── otp_input_widget.dart
```

### ৩.১ Models

#### user_model.dart
```dart
class UserModel {
  final int id;
  final String name;
  final String phone;
  final String? email;
  final String? avatar;
  final String walletBalance;
  final String referralCode;
  final bool isActive;

  UserModel({...});

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'],
      name: json['name'],
      phone: json['phone'],
      email: json['email'],
      avatar: json['avatar'],
      walletBalance: json['wallet_balance'] ?? '0.00',
      referralCode: json['referral_code'] ?? '',
      isActive: json['is_active'] ?? true,
    );
  }

  Map<String, dynamic> toJson() => {...};
}
```

#### auth_response_model.dart
```dart
class AuthResponseModel {
  final String? token;
  final bool isNewUser;
  final String? tempToken;
  final UserModel? user;

  AuthResponseModel({...});

  factory AuthResponseModel.fromJson(Map<String, dynamic> json) {...}
}
```

### ৩.২ Repository

#### auth_repository.dart (abstract)
```dart
abstract class AuthRepository {
  Future<ApiResponse> sendOtp(String phone);
  Future<AuthResponseModel> verifyOtp({
    required String phone,
    required String otp,
    String? name,
    String? fcmToken,
  });
  Future<AuthResponseModel> completeProfile({
    required String name,
    String? email,
    String? referralCode,
  });
  Future<void> logout();
}
```

#### auth_repository_impl.dart
```dart
class AuthRepositoryImpl implements AuthRepository {
  final DioClient _client;
  AuthRepositoryImpl(this._client);

  @override
  Future<ApiResponse> sendOtp(String phone) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.sendOtp,
        data: {'phone': phone},
      );
      return ApiResponse.fromJson(res.data, null);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<AuthResponseModel> verifyOtp({...}) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.verifyOtp,
        data: {
          'phone': phone,
          'otp': otp,
          if (name != null) 'name': name,
          if (fcmToken != null) 'fcm_token': fcmToken,
        },
      );
      return AuthResponseModel.fromJson(res.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  // ... completeProfile, logout
}
```

### ৩.৩ Provider

#### auth_provider.dart
```dart
class AuthProvider extends ChangeNotifier {
  final AuthRepository _repository;
  final SecureStorage _storage;

  AuthProvider(this._repository, this._storage);

  // State
  bool _isLoading = false;
  String? _error;
  UserModel? _user;
  String _phone = '';
  int _resendSeconds = 0;
  Timer? _resendTimer;

  // Getters
  bool get isLoading => _isLoading;
  String? get error => _error;
  UserModel? get user => _user;
  String get phone => _phone;
  int get resendSeconds => _resendSeconds;
  bool get canResend => _resendSeconds == 0;

  // Send OTP
  Future<bool> sendOtp(String phone) async {
    _setLoading(true);
    try {
      await _repository.sendOtp(phone);
      _phone = phone;
      _startResendTimer();
      _setLoading(false);
      return true;
    } on ApiException catch (e) {
      _setError(e.message);
      return false;
    }
  }

  // Verify OTP
  Future<AuthResult> verifyOtp(String otp, {String? name}) async {
    _setLoading(true);
    try {
      final fcmToken = await _getFcmToken();
      final result = await _repository.verifyOtp(
        phone: _phone, otp: otp, name: name, fcmToken: fcmToken,
      );

      if (result.isNewUser && result.token == null) {
        // need profile completion
        await _storage.saveToken(result.tempToken!);
        _setLoading(false);
        return AuthResult.needsProfile;
      }

      // logged in
      await _storage.saveToken(result.token!);
      await _storage.saveUser(jsonEncode(result.user!.toJson()));
      _user = result.user;
      _setLoading(false);
      return AuthResult.success;
    } on ApiException catch (e) {
      _setError(e.message);
      return AuthResult.failed;
    }
  }

  // Complete Profile
  Future<bool> completeProfile({...}) async {...}

  // Resend timer (30s countdown)
  void _startResendTimer() {
    _resendSeconds = AppConstants.otpResendSeconds;
    _resendTimer?.cancel();
    _resendTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_resendSeconds > 0) {
        _resendSeconds--;
        notifyListeners();
      } else {
        timer.cancel();
      }
    });
  }

  // helpers: _setLoading, _setError, _getFcmToken
}

enum AuthResult { success, needsProfile, failed }
```

### ৩.৪ Screens

#### phone_entry_screen.dart
```
Layout:
- Top: Title "আপনার ফোন নম্বর দিন" + subtitle
- Phone input field:
  - Prefix: "+880" বা "🇧🇩"
  - Validator: Validators.phone
  - Keyboard: number
- "Continue" button (CustomButton):
  - Loading state দেখাবে (provider.isLoading)
  - onPressed → provider.sendOtp() → success হলে /otp-verify
- Bottom: Terms & Privacy text links

Error: provider.error থাকলে snackbar দেখাবে
```

#### otp_verify_screen.dart
```
Layout:
- Top: "OTP যাচাই করুন" + "[phone] নম্বরে কোড পাঠানো হয়েছে"
- OTP input (pinput package — 6 boxes)
- Auto-submit যখন 6 digit complete
- Resend section:
  - canResend false → "আবার পাঠান (XX সেকেন্ড)"
  - canResend true → "আবার পাঠান" (clickable)
- "Verify" button:
  - onPressed → provider.verifyOtp()
  - AuthResult.success → /home
  - AuthResult.needsProfile → /complete-profile
  - AuthResult.failed → error snackbar
- "Change number" link → back

Auto-read OTP (optional): SMS autofill
```

#### complete_profile_screen.dart
```
শুধু নতুন user-এর জন্য

Layout:
- Title "আপনার তথ্য দিন"
- Name field (required)
- Email field (optional)
- Referral code field (optional) — "বন্ধুর code থাকলে দিন"
- "Complete" button:
  - onPressed → provider.completeProfile()
  - success → /home
```

#### otp_input_widget.dart
```
pinput দিয়ে 6-box OTP input
Themed (focused, filled states)
onCompleted callback
```

---

## ৪. Routing Update

```dart
// app_router.dart-এ যোগ করো

// Redirect logic
redirect: (context, state) async {
  final storage = context.read<SecureStorage>();
  final hasToken = await storage.hasToken();
  final loggingIn = state.matchedLocation == RouteNames.phoneEntry ||
                    state.matchedLocation == RouteNames.otpVerify;

  // Token আছে কিন্তু auth screen-এ → home
  if (hasToken && loggingIn) return RouteNames.home;
  return null;
},

routes: [
  GoRoute(path: RouteNames.splash, builder: (_, __) => const SplashScreen()),
  GoRoute(path: RouteNames.onboarding, builder: (_, __) => const OnboardingScreen()),
  GoRoute(path: RouteNames.phoneEntry, builder: (_, __) => const PhoneEntryScreen()),
  GoRoute(path: RouteNames.otpVerify, builder: (_, __) => const OtpVerifyScreen()),
  GoRoute(path: RouteNames.completeProfile, builder: (_, __) => const CompleteProfileScreen()),
  // home placeholder
]
```

---

## ৫. Provider Registration (app.dart)

```dart
MultiProvider(
  providers: [
    // Core (Phase 1)
    Provider<SecureStorage>(create: (_) => SecureStorage()),
    Provider<DioClient>(create: (ctx) => DioClient(ctx.read())),

    // Auth
    Provider<AuthRepository>(
      create: (ctx) => AuthRepositoryImpl(ctx.read<DioClient>()),
    ),
    ChangeNotifierProvider<AuthProvider>(
      create: (ctx) => AuthProvider(
        ctx.read<AuthRepository>(),
        ctx.read<SecureStorage>(),
      ),
    ),
  ],
  ...
)
```

---

## গুরুত্বপূর্ণ নিয়ম

1. **FCM token** verify-otp-এ পাঠাবে — Firebase initialize হওয়ার পরে
2. OTP screen-এ **back press** করলে phone screen-এ ফিরবে (timer reset)
3. Loading state-এ button **disable** + spinner
4. Error সব **snackbar** দিয়ে দেখাবে (snackbar_helper)
5. Phone number **format**: শুধু 11 digit (01XXXXXXXXX)
6. `provider.dispose()`-এ timer cancel করবে
7. design image থাকলে **সেটা priority** — আমার layout শুধু fallback

---

## এই Phase-এ Deliverable

- [ ] Splash feature (auto-redirect logic)
- [ ] Onboarding feature (3 slides)
- [ ] Auth models (UserModel, AuthResponseModel)
- [ ] Auth repository (abstract + impl)
- [ ] AuthProvider (sendOtp, verifyOtp, completeProfile, resend timer)
- [ ] Phone Entry screen
- [ ] OTP Verify screen (pinput, auto-submit, resend)
- [ ] Complete Profile screen
- [ ] Routing update (redirect logic)
- [ ] Provider registration

---

## শুরু করো এই order-এ

1. Splash feature
2. Onboarding feature
3. Auth models
4. Auth repository (abstract → impl)
5. AuthProvider
6. Phone Entry screen
7. OTP Verify screen + otp_input_widget
8. Complete Profile screen
9. Routing + Provider registration
10. Test: Phone → OTP → (Profile) → Home placeholder
    Development-এ OTP backend log থেকে দেখবে
