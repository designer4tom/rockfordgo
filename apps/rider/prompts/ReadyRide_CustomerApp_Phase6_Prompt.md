# ReadyRide — Customer App (Flutter)
# Phase 6: Real-time Tracking (Ride + Parcel)

---

## Context
Phase 1-5 শেষ। Booking হয়ে গেছে, driver assigned।
এই phase-এ real-time tracking, driver info, trip flow, completion বানাবো।

Architecture: **MVVM + Provider** | Real-time: **pusher_channels_flutter**

---

## 🎨 Design Image Instruction
> Design image দিলে হুবহু follow করবে।

---

## Feature

```
features/tracking/
├── provider/tracking_provider.dart
├── repository/
│   ├── tracking_repository.dart
│   └── tracking_repository_impl.dart
├── model/
│   └── tracking_model.dart
└── views/
    ├── screen/
    │   ├── ride_tracking_screen.dart
    │   ├── parcel_tracking_screen.dart
    │   ├── trip_complete_screen.dart
    │   └── rating_screen.dart
    └── widgets/
        ├── tracking_map.dart
        ├── driver_info_card.dart
        ├── trip_status_bar.dart
        ├── otp_display.dart
        ├── tip_selector.dart
        └── sos_button.dart
```

---

## ১. Pusher Service (core)

```dart
// core/network/pusher_service.dart

class PusherService {
  late PusherChannelsFlutter _pusher;

  Future<void> init();  // config থেকে key, cluster
  Future<void> subscribe(String channel, Function(dynamic) onEvent);
  Future<void> unsubscribe(String channel);
  Future<void> disconnect();
}
```

---

## ২. Tracking Model

```dart
class TrackingModel {
  final String status;
  final DriverInfo? driver;
  final double? driverLat, driverLng, driverBearing;
  final String? otp;
  final int? estimatedArrival;
  // fromJson + copyWith for live updates
}
```

---

## ৩. Tracking Provider

```dart
class TrackingProvider extends ChangeNotifier {
  final TrackingRepository _repository;
  final PusherService _pusher;

  TrackingModel? tracking;
  String orderType = 'ride';  // ride/parcel
  int? orderId;

  // Map markers
  LatLng? driverPosition;
  double driverBearing = 0;

  // Methods
  Future<void> initTracking(int orderId, String type);
  void subscribeToPusher(int orderId);  // private-order.{id}

  // Pusher event handlers
  void _onDriverLocationUpdated(data);  // update marker
  void _onStatusUpdated(data);          // status change
  void _onOrderCompleted(data);         // → complete screen
  void _onOrderCancelled(data);         // → home

  // Actions
  Future<void> callDriver();            // url_launcher
  Future<String> shareTrip();           // generate share link
  Future<void> triggerSos();
  Future<bool> cancelOrder(String reason);

  void dispose();  // unsubscribe pusher
}
```

---

## ৪. Screens

### ride_tracking_screen.dart
```
Layout (Stack):

Map (full screen) — tracking_map:
- Driver marker (live, animated movement)
- Pickup ও Drop markers
- Route polyline
- Camera follows driver

Top bar:
- Back/minimize button
- Trip status (trip_status_bar):
  "Driver আসছে" / "যাত্রা চলছে" ইত্যাদি

Bottom sheet (driver_info_card):
- Driver avatar, name, rating
- Vehicle: Make Model, color, reg number
- Estimated arrival
- Action buttons:
  - 📞 Call driver
  - 💬 Chat (optional)
  - 📍 Share trip
- OTP display (otp_display) — large, prominent
  "Driver-কে এই OTP বলুন: 4521"
- SOS button (sos_button) — always visible
- Cancel button (early stages)

Status-based UI:
- accepted/go_to_pickup → "Driver আসছে" + OTP
- confirm_arrival → "Driver পৌঁছেছে" + OTP highlighted
- picked_up/start_ride → "যাত্রা চলছে", OTP hide
- dropped_off → payment prompt
- completed → /trip-complete
```

### parcel_tracking_screen.dart
```
Similar to ride কিন্তু:
- Sender/Receiver info instead of passenger
- Parcel details
- COD reminder (যদি COD)
- Proof of delivery status
- After Pay → payment prompt on delivery
```

### trip_complete_screen.dart
```
- Success animation (lottie)
- "Trip সম্পন্ন!"
- Fare summary
- Payment status:
  - Cash → "Driver-কে ৳XX দিন"
  - Online/Wallet → "Paid"
- Tip section (tip_selector) — যদি tip_enabled:
  - Quick amounts: ৳10, ৳20, ৳50, ৳100, Custom
  - "Tip দিন" button
- "Rate your trip" → /rating
- "Done" → /home
- Invoice link
```

### rating_screen.dart
```
- Driver avatar + name
- Star rating (1-5, tappable)
- Quick tags (chips):
  "সময়মতো এসেছে", "ভালো ব্যবহার", "গাড়ি পরিষ্কার", "নিরাপদ ড্রাইভিং"
- Comment textarea (optional)
- "Submit" → provider.rate → /home
- "Skip" → /home
```

---

## ৫. Widgets

```
tracking_map.dart      — Google Map, animated driver marker, route
driver_info_card.dart  — driver details + actions
trip_status_bar.dart   — current status label + icon
otp_display.dart       — big OTP boxes
tip_selector.dart      — quick amounts + custom
sos_button.dart        — red SOS, confirm dialog → trigger
```

### Animated Driver Marker
```dart
// Driver location update হলে smooth animation
// পুরনো → নতুন position interpolate
// Bearing অনুযায়ী icon rotate
```

---

## ৬. Routing

```dart
GoRoute(path: RouteNames.rideTracking, builder: (_, s) =>
    RideTrackingScreen(orderId: s.extra as int)),
GoRoute(path: RouteNames.parcelTracking, builder: (_, s) =>
    ParcelTrackingScreen(orderId: s.extra as int)),
GoRoute(path: '/trip-complete', builder: (_, s) =>
    TripCompleteScreen(orderId: s.extra as int)),
GoRoute(path: '/rating', builder: (_, s) =>
    RatingScreen(orderId: s.extra as int)),
```

---

## গুরুত্বপূর্ণ নিয়ম

1. **Pusher** primary — real-time updates। Polling শুধু fallback
2. Driver marker **smooth animation** — teleport না
3. OTP **শুধু pickup-এর আগে** দেখাবে, picked_up হলে hide
4. SOS **confirm dialog** → তারপর trigger
5. Screen leave → Pusher **unsubscribe**
6. App background → foreground এলে status **re-fetch**
7. Trip share link → **share_plus** দিয়ে share

---

## Deliverable

- [ ] Pusher service
- [ ] Tracking model + repository + provider
- [ ] Ride tracking screen (live map + driver card + OTP + SOS)
- [ ] Parcel tracking screen
- [ ] Trip complete screen (tip)
- [ ] Rating screen
- [ ] All widgets (animated marker)
- [ ] Routing + Provider

---

## শুরু করো এই order-এ

1. Pusher service
2. Tracking model, repository, provider
3. Tracking map widget (animated marker)
4. Driver info card, status bar, OTP, SOS widgets
5. Ride tracking screen
6. Parcel tracking screen
7. Trip complete + tip
8. Rating screen
9. Routing + Provider
10. Test: Book → track (Pusher updates) → complete → tip → rate
