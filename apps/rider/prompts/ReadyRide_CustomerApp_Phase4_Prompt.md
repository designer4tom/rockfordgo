# ReadyRide — Customer App (Flutter)
# Phase 4: Ride Booking Flow

---

## Context
Phase 1-3 শেষ। User pickup ও drop select করেছে।
এই phase-এ Vehicle select → Fare → Book → Driver search → Tracking entry।

Architecture: **MVVM + Provider** | API: **Dio**

---

## 🎨 Design Image Instruction
> Design image দিলে হুবহু follow করবে। না দিলে নিচের reference।

---

## Feature

```
features/ride/
├── provider/ride_provider.dart
├── repository/
│   ├── ride_repository.dart
│   └── ride_repository_impl.dart
├── model/
│   ├── fare_estimate_model.dart
│   ├── ride_booking_model.dart
│   └── ride_status_model.dart
└── views/
    ├── screen/
    │   ├── vehicle_select_screen.dart
    │   ├── booking_confirm_screen.dart
    │   └── searching_driver_screen.dart
    └── widgets/
        ├── vehicle_category_card.dart
        ├── fare_breakdown_card.dart
        ├── payment_method_selector.dart
        ├── coupon_input.dart
        └── schedule_picker.dart
```

---

## ১. Models

### fare_estimate_model.dart
```dart
class FareEstimateModel {
  final int vehicleCategoryId;
  final String vehicleCategoryName;
  final double distanceKm;
  final int durationMinutes;
  final FareBreakdown breakdown;
  final String totalFare, minimumFare, finalFare;
  final bool surgeActive;
  // fromJson
}

class FareBreakdown {
  final String baseFare, distanceCharge, timeCharge, surgeAmount;
  final double surgeMultiplier;
  // fromJson
}
```

### ride_booking_model.dart
```dart
class RideBookingModel {
  final int orderId;
  final String orderNumber, status, otp;
  final FareBreakdown fare;
  final String paymentMethod;
  final PlaceModel pickup, drop;
  // fromJson
}
```

### ride_status_model.dart
```dart
class RideStatusModel {
  final String status;       // pending/accepted/.../completed
  final String message;
  final DriverInfo? driver;
  final String? otp;
  // fromJson
}

class DriverInfo {
  final int id;
  final String name, phone;
  final String? avatar;
  final String rating;
  final VehicleInfo vehicle;
  final double currentLat, currentLng;
  final int estimatedArrival;
  // fromJson
}
```

---

## ২. Repository

```dart
abstract class RideRepository {
  Future<FareEstimateModel> getFareEstimate({
    required int vehicleCategoryId,
    required PlaceModel pickup,
    required PlaceModel drop,
    List<PlaceModel>? stops,
  });

  Future<RideBookingModel> bookRide({...});
  Future<RideStatusModel> getStatus(int orderId);
  Future<Map> cancelRide(int orderId, String reason);
  Future<void> addTip(int orderId, double amount);
  Future<void> rateRide(int orderId, int rating, String? comment, List<String> tags);
  Future<String> generateShareLink(int orderId);

  // Coupon
  Future<CouponModel> validateCoupon(String code, String serviceType, double amount);
}
```

---

## ৩. Provider

```dart
class RideProvider extends ChangeNotifier {
  final RideRepository _repository;

  // Booking state
  PlaceModel? pickup, drop;
  List<PlaceModel> stops = [];
  int? selectedCategoryId;
  List<FareEstimateModel> fareEstimates = [];
  String paymentMethod = 'cash';
  CouponModel? appliedCoupon;
  DateTime? scheduledAt;
  bool rideShare = false;

  // Active ride
  RideBookingModel? activeBooking;
  RideStatusModel? rideStatus;
  Timer? _statusPollTimer;

  bool isLoading = false;
  String? error;

  // Methods
  Future<void> loadFareEstimates();       // সব category-র fare
  void selectCategory(int id);
  Future<bool> applyCoupon(String code);
  void setPaymentMethod(String method);
  void setSchedule(DateTime? dt);

  Future<bool> bookRide();                 // book + start polling
  void startStatusPolling(int orderId);    // every 3s
  Future<bool> cancelRide(String reason);
  Future<void> addTip(double amount);
  Future<void> rateRide(int rating, String? comment, List<String> tags);

  void clearBooking();                     // reset state
}
```

---

## ৪. Screens

### vehicle_select_screen.dart
```
Layout:
- Top: Map (pickup → drop route line, smaller height)
- Pickup/Drop summary bar
- Vehicle category list (vehicle_category_card):
  প্রতিটা card:
  - Icon + Name
  - Capacity (e.g. "1 seat")
  - Estimated arrival ("4 min away")
  - Fare (right side, bold)
  - Surge badge (যদি active)
  - Selected → highlighted border
- Bottom: "Continue" button → /booking-confirm

onInit: provider.loadFareEstimates() (loading shimmer)
```

### booking_confirm_screen.dart
```
Layout:
- Map (small)
- Selected vehicle summary
- Fare Breakdown card (fare_breakdown_card):
  Base + Distance + Time + Surge + Coupon discount = Total
- Coupon input (coupon_input):
  - Text field + "Apply" button
  - Applied → green check + discount shown + remove option
- Payment method selector (payment_method_selector):
  - Cash / Online (Card) / Wallet (balance shown)
- Schedule option (schedule_picker) — যদি config-এ enabled:
  - "এখনই" / "পরে" toggle
  - "পরে" → date-time picker
- Bottom: "Confirm Booking" button (fare দেখাবে)
  → provider.bookRide()
  → success → /searching-driver
```

### searching_driver_screen.dart
```
Layout:
- Animated searching (lottie/pulse)
- "Driver খোঁজা হচ্ছে..." text
- Cancel button
- Background: provider polls status every 3s

Status change:
- accepted → navigate /ride-tracking (Phase 6)
- no_driver_found → dialog "Driver পাওয়া যায়নি" + Retry/Cancel
- cancelled → back to home
```

---

## ৫. Widgets

### vehicle_category_card.dart
```
Props: category, isSelected, onTap, fareEstimate
Loading state → shimmer
```

### fare_breakdown_card.dart
```
Props: breakdown, couponDiscount, total
Itemized rows + divider + total (bold)
```

### payment_method_selector.dart
```
Props: selected, walletBalance, onChanged
Radio-style cards: Cash, Online, Wallet
Wallet-এ balance insufficient হলে disabled + "Top up" hint
```

### coupon_input.dart
```
Props: onApply, appliedCoupon, onRemove
States: empty / applying / applied / error
```

### schedule_picker.dart
```
Props: onScheduled
"এখনই" / "পরে" segmented
"পরে" → showDateTimePicker (min: now+1h, max: config days)
```

---

## ৬. Routing

```dart
GoRoute(path: RouteNames.vehicleSelect, builder: (_, __) => const VehicleSelectScreen()),
GoRoute(path: RouteNames.bookingConfirm, builder: (_, __) => const BookingConfirmScreen()),
GoRoute(path: '/searching-driver', builder: (_, __) => const SearchingDriverScreen()),
```

---

## গুরুত্বপূর্ণ নিয়ম

1. Fare estimate **loading** → shimmer cards
2. Status polling **3 সেকেন্ড** — accepted হলে stop + navigate
3. Wallet balance insufficient → book block + top-up suggest
4. Coupon apply **before booking** — discount fare-এ reflect
5. Scheduled booking → status polling skip (পরে assign হবে)
6. Screen leave → polling timer cancel

---

## Deliverable

- [ ] Ride models (fare, booking, status)
- [ ] Ride repository + provider
- [ ] Vehicle Select screen
- [ ] Booking Confirm screen (fare, coupon, payment, schedule)
- [ ] Searching Driver screen (polling)
- [ ] All widgets
- [ ] Routing + Provider

---

## শুরু করো এই order-এ

1. Models
2. Repository + Provider
3. Vehicle Select screen + card
4. Booking Confirm + widgets (fare, coupon, payment, schedule)
5. Searching Driver screen
6. Routing + Provider
7. Test: Select vehicle → confirm → book → searching (driver accept simulate)
