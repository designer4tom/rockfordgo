# ReadyRide — Customer App (Flutter)
# Phase 5: Parcel Booking Flow

---

## Context
Phase 1-4 শেষ। এই phase-এ Parcel booking flow বানাবো।

Architecture: **MVVM + Provider** | API: **Dio**

---

## 🎨 Design Image Instruction
> Design image দিলে হুবহু follow করবে। না দিলে নিচের reference।

---

## Feature

```
features/parcel/
├── provider/parcel_provider.dart
├── repository/
│   ├── parcel_repository.dart
│   └── parcel_repository_impl.dart
├── model/
│   ├── parcel_estimate_model.dart
│   └── parcel_booking_model.dart
└── views/
    ├── screen/
    │   ├── parcel_sender_screen.dart
    │   ├── parcel_receiver_screen.dart
    │   ├── parcel_details_screen.dart
    │   ├── parcel_cod_screen.dart
    │   └── parcel_confirm_screen.dart
    └── widgets/
        ├── parcel_type_selector.dart
        ├── parcel_size_selector.dart
        ├── cod_input.dart
        └── parcel_step_indicator.dart
```

---

## ১. Models

```dart
class ParcelEstimateModel {
  final double distanceKm;
  final String deliveryCharge;
  final ParcelChargeBreakdown breakdown;
  final List<String> paymentTimingOptions;  // ['before', 'after']
  final bool codAvailable;
  // fromJson
}

class ParcelBookingModel {
  final int orderId;
  final String orderNumber, status;
  final String deliveryCharge;
  final String? codAmount, totalReceiverPays;
  final String paymentTiming, paymentMethod;
  final PersonInfo sender, receiver;
  // fromJson
}
```

---

## ২. Provider — Multi-step Form State

```dart
class ParcelProvider extends ChangeNotifier {
  final ParcelRepository _repository;

  // Step 1 — Sender
  String senderName = '', senderPhone = '';
  PlaceModel? pickup;

  // Step 2 — Receiver
  String receiverName = '', receiverPhone = '';
  PlaceModel? drop;

  // Step 3 — Parcel
  String parcelType = 'normal';   // normal/fragile/document
  double weight = 0.5;
  String size = 'small';          // small/medium/large
  String? parcelNote;
  File? parcelPhoto;

  // Step 4 — COD
  bool isCod = false;
  double? codAmount;

  // Step 5 — Payment
  ParcelEstimateModel? estimate;
  String paymentTiming = 'before';
  String paymentMethod = 'wallet';
  CouponModel? appliedCoupon;

  int currentStep = 0;

  // Methods
  void setSenderInfo({...});
  void setReceiverInfo({...});
  void setParcelDetails({...});
  void setCod(bool enabled, double? amount);
  Future<void> loadEstimate();
  Future<bool> applyCoupon(String code);
  Future<ParcelBookingModel?> bookParcel();
  void nextStep();
  void prevStep();
  void reset();
}
```

---

## ৩. Screens (Multi-step)

### parcel_step_indicator.dart
```
Top progress indicator — 5 steps:
Sender → Receiver → Details → COD → Confirm
Current step highlighted
```

### parcel_sender_screen.dart
```
- Step indicator (step 1)
- "প্রেরকের তথ্য" title
- Sender name (default: user name)
- Sender phone (default: user phone)
- Pickup location (search বা current)
- "Next" → receiver screen
```

### parcel_receiver_screen.dart
```
- Step indicator (step 2)
- "প্রাপকের তথ্য" title
- Receiver name (required)
- Receiver phone (required)
- Drop location (search/map)
- "Next" → details screen
```

### parcel_details_screen.dart
```
- Step indicator (step 3)
- "পার্সেলের তথ্য"
- Parcel type selector (parcel_type_selector): Normal/Fragile/Document
- Weight input (kg) — slider বা input
- Size selector (parcel_size_selector): Small/Medium/Large (icons)
- Photo (optional): camera/gallery → image_picker
- Note (optional textarea)
- "Next" → COD screen (যদি cod_enabled) নয়তো confirm
```

### parcel_cod_screen.dart
```
শুধু cod_enabled হলে দেখাবে

- Step indicator (step 4)
- "COD (Cash on Delivery)"
- COD toggle: হ্যাঁ / না
- COD হ্যাঁ হলে:
  - Product price input
  - Info box: "Receiver দেবে = Product Price + Delivery Charge"
  - "Delivery সফল হলে আপনার wallet-এ আসবে: Product Price - Delivery Charge"
- "Next" → confirm
```

### parcel_confirm_screen.dart
```
- Step indicator (step 5)
- Full summary:
  - Sender → Receiver
  - Parcel details
  - COD info (যদি)
- Delivery charge breakdown (loadEstimate)
- Coupon input
- Payment timing selector (যদি both available): Before / After
- Payment method (Before হলে): Cash/Online/Wallet
- "Confirm Booking" button
  → provider.bookParcel()
  → success → /parcel-tracking (Phase 6)
```

---

## ৪. Widgets

```
parcel_type_selector.dart  — 3 cards: Normal/Fragile/Document
parcel_size_selector.dart  — 3 cards with icons: S/M/L
cod_input.dart             — toggle + amount + info box
parcel_step_indicator.dart — 5-step progress
```

---

## ৫. Routing

```dart
GoRoute(path: RouteNames.parcelBooking, builder: (_, __) => const ParcelSenderScreen()),
GoRoute(path: '/parcel-receiver', builder: (_, __) => const ParcelReceiverScreen()),
GoRoute(path: '/parcel-details', builder: (_, __) => const ParcelDetailsScreen()),
GoRoute(path: '/parcel-cod', builder: (_, __) => const ParcelCodScreen()),
GoRoute(path: '/parcel-confirm', builder: (_, __) => const ParcelConfirmScreen()),
```

Or single screen with PageView (better state management)

---

## গুরুত্বপূর্ণ নিয়ম

1. Multi-step **state preserve** — back করলে data থাকবে
2. COD screen **conditional** — config.cod_enabled false হলে skip
3. Payment timing **config-based** — before/after/both
4. Photo upload **multipart** form data
5. Step indicator সব screen-এ consistent
6. Each step **validation** — incomplete হলে next disable

---

## Deliverable

- [ ] Parcel models
- [ ] Parcel repository + provider (multi-step state)
- [ ] 5 step screens
- [ ] Step indicator + widgets
- [ ] Routing + Provider

---

## শুরু করো এই order-এ

1. Models
2. Repository + Provider
3. Step indicator widget
4. Sender → Receiver → Details → COD → Confirm screens
5. Type/Size selectors, COD input
6. Routing + Provider
7. Test: Full parcel booking flow → book → tracking placeholder
