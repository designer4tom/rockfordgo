# ReadyRide — Driver App (Flutter)
# Phase 2: Authentication + Onboarding (Document Submission)

---

## Context
Phase 1 শেষ — Core ready। এই phase-এ Driver auth ও registration (document upload)।

Architecture: **MVVM + Provider** | API: **Dio**

---

## 🎨 Design Image Instruction
> Design image দিলে হুবহু follow করবে। না দিলে নিচের reference।

---

## Features

```
features/
├── splash/
├── onboarding/
└── auth/
```

---

## ১. Splash Feature

### Logic
```
1. Logo দেখাবে (1.5s)
2. Check:
   - Token আছে? → status check API
     - approved → /home
     - pending → /pending-approval
     - rejected → /registration (with rejection reason)
   - Token নেই → onboarding/phone-entry
3. Config cache
```

---

## ২. Onboarding Feature

### Content (Driver-focused)
```
Slide 1: "নিজের সময়ে কাজ করুন" — Flexibility
Slide 2: "প্রতি ট্রিপে আয় করুন" — Earnings
Slide 3: "সহজ withdrawal" — Easy payout
```

---

## ৩. Auth Feature

```
features/auth/
├── provider/
│   ├── auth_provider.dart
│   └── registration_provider.dart
├── repository/
│   ├── auth_repository.dart
│   └── auth_repository_impl.dart
├── model/
│   ├── driver_model.dart
│   └── auth_response_model.dart
└── views/
    ├── screen/
    │   ├── phone_entry_screen.dart
    │   ├── otp_verify_screen.dart
    │   ├── registration_screen.dart
    │   └── pending_approval_screen.dart
    └── widgets/
        ├── otp_input_widget.dart
        ├── document_upload_field.dart
        └── registration_step.dart
```

### ৩.১ Models

```dart
class DriverModel {
  final int id;
  final String name, phone;
  final String? email, avatar;
  final String status;          // pending/approved/rejected/suspended/blocked
  final bool isOnline;
  final String walletBalance, dueAmount, averageRating;
  final int totalTrips;
  final ZoneInfo? zone;
  final VehicleInfo? vehicle;
  final String? rejectionReason;
  // fromJson
}
```

### ৩.২ Auth Provider
```dart
class AuthProvider extends ChangeNotifier {
  // sendOtp, verifyOtp (driver type)
  // 30s resend timer

  Future<AuthResult> verifyOtp(String otp);
  // Returns:
  //   approved → AuthResult.approved (→ home)
  //   new/pending → AuthResult.needsRegistration বা pending
  //   blocked → AuthResult.blocked

  Future<DriverStatus> checkStatus();  // status API
}

enum AuthResult { approved, needsRegistration, pending, rejected, blocked, failed }
```

### ৩.৩ Registration Provider (Multi-step document upload)
```dart
class RegistrationProvider extends ChangeNotifier {
  // Personal
  String name = '', email = '';

  // Documents (File)
  File? nidFront, nidBack;
  File? licenseFront;
  DateTime? licenseExpiry;
  File? vehicleRegDoc;
  DateTime? vehicleRegExpiry;
  File? insuranceDoc;
  DateTime? insuranceExpiry;
  File? vehicleFrontPhoto, vehicleBackPhoto;

  // Vehicle
  int? vehicleCategoryId;
  String vehicleMake = '', vehicleModel = '', vehicleYear = '', vehicleColor = '';
  String vehicleRegNumber = '';

  // Withdrawal
  String withdrawalMethod = 'bkash';
  String withdrawalAccount = '';

  List<VehicleCategoryModel> categories = [];

  int currentStep = 0;  // 0:personal 1:documents 2:vehicle 3:bank

  // Methods
  Future<void> loadVehicleCategories();
  void pickImage(String field, ImageSource source);  // image_picker
  void setExpiry(String field, DateTime date);
  bool validateStep(int step);
  Future<bool> submitRegistration();  // multipart all data
  void nextStep();
  void prevStep();
}
```

### ৩.৪ Screens

#### phone_entry_screen.dart
```
(Customer-এর মতোই কিন্তু driver endpoint)
"Driver হিসেবে যোগ দিন"
```

#### otp_verify_screen.dart
```
(Customer-এর মতোই)
verify result অনুযায়ী navigate:
- approved → /home
- new → /registration
- pending → /pending-approval
- blocked → blocked dialog
```

#### registration_screen.dart (Multi-step)
```
PageView/Stepper — 4 steps:

Step 1 — Personal Info:
- Name (required)
- Email (optional)

Step 2 — Documents (document_upload_field for each):
- NID Front + Back
- Driving License + expiry date
- Vehicle Registration + expiry
- Insurance + expiry
- Vehicle Front + Back photo
প্রতিটা: image picker (camera/gallery) + preview + expiry date (যেখানে দরকার)

Step 3 — Vehicle Info:
- Vehicle Category (dropdown — loadVehicleCategories)
- Make, Model, Year, Color
- Registration Number

Step 4 — Withdrawal Info:
- Method: bKash / Nagad / Bank (radio)
- Account number/details

Submit → multipart upload → /pending-approval

Progress indicator top-এ
Each step validation
```

#### pending_approval_screen.dart
```
- Illustration (waiting/review)
- "আপনার আবেদন পর্যালোচনাধীন"
- "Admin আপনার documents যাচাই করছেন"
- Document status list:
  প্রতিটা document: name + status badge (pending/approved/rejected)
- Refresh button (check status)
- Rejected হলে: reason দেখাবে + "আবার submit করুন" → /registration
- Approved হলে: auto → /home (অথবা "Continue" button)
- Logout option
```

#### document_upload_field.dart
```
Props: label, file, onPick, hasExpiry, expiryDate, onExpiryPick
- Upload area (tap → camera/gallery sheet)
- Preview (picked হলে thumbnail)
- Expiry date picker (যদি hasExpiry)
- Required indicator
```

---

## ৪. Routing

```dart
GoRoute(path: RouteNames.splash, ...),
GoRoute(path: RouteNames.onboarding, ...),
GoRoute(path: RouteNames.phoneEntry, ...),
GoRoute(path: RouteNames.otpVerify, ...),
GoRoute(path: RouteNames.registration, ...),
GoRoute(path: RouteNames.pendingApproval, ...),

// Redirect:
// - approved + auth screen → home
// - pending + non-pending screen → pending-approval
```

---

## গুরুত্বপূর্ণ নিয়ম

1. Document upload **multipart** — image compress করবে (বড় file না)
2. Expiry date **future** validation
3. Registration incomplete → draft **locally save** (পরে continue)
4. Pending screen → status **poll/refresh** (auto check)
5. Rejected → reason clear দেখাবে
6. Image picker → **compress** (max 1MB) before upload

---

## Deliverable

- [ ] Splash (status-based redirect)
- [ ] Onboarding (driver slides)
- [ ] Driver model, auth response
- [ ] Auth repository + provider
- [ ] Registration provider (multi-step)
- [ ] Phone, OTP screens
- [ ] Registration screen (4 steps, document upload)
- [ ] Pending approval screen
- [ ] Document upload field widget
- [ ] Routing + Providers

---

## শুরু করো এই order-এ

1. Splash + Onboarding
2. Models (DriverModel, AuthResponse)
3. Auth repository + provider
4. Phone + OTP screens
5. Registration provider
6. Registration screen (4 steps) + document_upload_field
7. Pending approval screen
8. Routing + Providers
9. Test: Phone → OTP → Register (upload docs) → Pending → (admin approve) → Home
